import {Component, AfterViewInit, ViewChild, OnDestroy, OnInit} from '@angular/core';
import {Observable, merge, of, interval } from 'rxjs';
import {catchError, map, startWith, switchMap} from 'rxjs/operators';
import {Location} from '@angular/common';
import {ActivatedRoute, Router} from '@angular/router';

import { CalloutDetailsService } from './callout-details.service';
import { GoogleApiService } from '../common/google-api.service';

import {
  MatButtonModule,
  MatIconModule,
} from '@angular/material';

@Component({
  selector: 'app-callout-details',
  templateUrl: './callout-details.component.html',
  styleUrls: ['./callout-details.component.css']
})

export class CalloutDetailsComponent implements AfterViewInit, OnInit, OnDestroy {

    isLoadingResults: Observable<boolean> = of(true);

    private calloutDetails: Observable<any> = null;
    private sub: any;
    private cid: number;
    private ckid: string;
    private member_id: string;

    private autoRefreshTimer;
    private countDownTimer;

    direction: Observable<any>;
    markers$: Observable<MapMarker[]>;
    defaultStatusResponse$: Observable<any>;

    constructor(private location: Location,
                private calloutDetailsService: CalloutDetailsService,
                private route: ActivatedRoute, private router: Router,
                private googleApiService: GoogleApiService) {
    }

    ngOnInit() {
      // debugger;
      this.sub = this.route
        .queryParams
        .subscribe(params => {
          this.cid = +params['cid'] || 0;
          this.ckid = params['ckid'] || '';
          this.member_id = params['member_id'] || '';
      });
    }

    ngOnDestroy() {
      // debugger;
      if (this.sub !== undefined) {
        this.sub.unsubscribe();
      }
      if (this.autoRefreshTimer !== undefined) {
        this.autoRefreshTimer.unsubscribe();
      }
      if (this.countDownTimer !== undefined) {
        this.countDownTimer.unsubscribe();
      }
    }

    ngAfterViewInit() {
      // debugger;

      merge()
        .pipe(
          startWith({}),
          switchMap(() => {
            // debugger;
            Promise.resolve(null).then(() => this.isLoadingResults = of(true));
            return this.calloutDetailsService.getDetails(this.cid, this.ckid, this.member_id);
          }),
          map(data => {
            // debugger;

            this.calloutDetails = of(data);
            // console.log('Got callout details: ' + this.calloutDetails);
            this.getMapMarkers(data);
            Promise.resolve(null).then(() => {
              this.defaultStatusResponse$ = this.getDefaultResponseStatus(data);
              this.isLoadingResults = of(false);
            });
            return data;
          }),
          catchError((err) => {
            debugger;

            console.log('Error getting grid data: ' + err);
            Promise.resolve(null).then(() =>
              this.isLoadingResults = of(false)
            );
            return of([]);
          })
        ).subscribe(data => {
          // debugger;
          this.calloutDetails.subscribe(callout => {
            this.setupAutoRefresh(callout);
          });
        }
      );
    }

    setupAutoRefresh(callout) {
      // debugger;
      const currentInstance = this;
      if (this.isCalloutPending(callout)) {
        // debugger;
        let MAP_AUTO_REFRESH_SECONDS = 60;
        this.autoRefreshTimer = interval(MAP_AUTO_REFRESH_SECONDS * 1000)
        .subscribe(i => {
            currentInstance.reloadData();
        });
        this.countDownTimer = interval(1000)
        .subscribe(i => {
            const ui_refresh = document.getElementById('reload_timer_ui');
            if (ui_refresh !== undefined) {
                MAP_AUTO_REFRESH_SECONDS--;
                ui_refresh.textContent = MAP_AUTO_REFRESH_SECONDS + ' seconds until auto refresh';
            }
        });
      }
    }

    reloadData() {
      // debugger;
      if (this.autoRefreshTimer !== undefined) {
        this.autoRefreshTimer.unsubscribe();
      }
      if (this.countDownTimer !== undefined) {
        this.countDownTimer.unsubscribe();
      }
      this.ngAfterViewInit();
    }

    isRequestValid(callout): boolean {
      return callout.firehall_id !== undefined && callout.details !== undefined &&
             callout.details.call_key !== undefined && callout.details.id !== -1;
    }

    isAllowedToEditResponse(callout, row_responding): boolean {
      return ((callout.member_access_respond_self && row_responding.user_id === callout.member_id) ||
              (callout.member_access_respond_others && row_responding.user_id !== callout.member_id)) &&
             ((callout.ALLOW_CALLOUT_UPDATES_AFTER_FINISHED && !row_responding.callout_status_entity.is_cancelled &&
               !row_responding.callout_status_entity.is_completed) ||
               (!callout.details.callout_status_completed && !callout.details.callout_status_cancelled &&
                !row_responding.callout_status_entity.is_cancelled && !row_responding.callout_status_entity.is_completed &&
                !row_responding.callout_status_entity.is_not_responding));
    }

    onChangeResponse($event, responder_id, callout) {
      // debugger;
      const index = $event.target.selectedIndex;
      const text = $event.target[index].text;
      const value = $event.target[index].value.split(' ')[1];
      this.updateResponderStatus(callout, responder_id, value, text);
    }

    setResponse(row_no, callout) {
      // debugger;
      const status: HTMLSelectElement = <HTMLSelectElement>document.getElementById('ui_call_set_response_status' + row_no.id);
      const status_id = status.value.split(' ')[1];
      const statusDef = this.findStatusByCriteria(callout, status_id, false, false);
      const status_name = statusDef.displayName;
      return this.updateResponderStatus(callout, row_no.user_id, status_id, status_name);
    }

    setResponseCompleted(row_yes, callout) {
      // debugger;
      const status = this.findStatusByCriteria(callout, null, true, false);
      const status_name = status.displayName;
      const status_id = status.id;
      return this.updateResponderStatus(callout, row_yes.user_id, status_id, status_name);
    }

    setResponseCancelled(row_yes, callout) {
      // debugger;
      const status = this.findStatusByCriteria(callout, null, false, true);
      const status_name = status.displayName;
      const status_id = status.id;
      return this.updateResponderStatus(callout, row_yes.user_id, status_id, status_name);
    }

    updateResponderStatus(callout, responder_id, status_id, status_name) {
      // debugger;
      if (confirm(`Confirm that ${responder_id}'s status should be changed to ${status_name} ?`)) {
          return this.calloutDetailsService.updateResponse(callout.details.id,
            callout.details.call_key, callout.member_id, responder_id, status_id).subscribe( response => {
            this.reloadData();
            return true;
          });
      }
      return false;
    }

    private findStatusByCriteria(callout, id, completed, cancelled) {
      const statusFound = callout.callout_status_defs.filter(function(statusDef) {
        if (id !== null) {
          return statusDef.id === id;
        }
        if (completed) {
          return statusDef.is_completed && statusDef.is_default_response;
        }
        if (cancelled) {
          return statusDef.is_cancelled && statusDef.is_default_response;
        }
      });
      if (statusFound != null && statusFound.length >= 1) {
        return statusFound[0];
      }
      return null;
    }

    getUrl(url: string): string {
      const newUrl = window.location.origin + this.getRootPath() + url;
      return newUrl;
    }

    getMapMarkers(data) {
        // debugger;
        const calloutDest = data.details.callout_geo_dest.split(',').map(Number);
        if (calloutDest != null && calloutDest.length === 2 &&
            calloutDest[0] === 0.0 && calloutDest[1] === 0.0) {
          this.googleApiService.getGEOCoordinatesFromAddress(data.details.callout_address_dest).
            subscribe(result => {
              // debugger;
              data.details.latitude = result[0];
              data.details.longitude = result[1];
              this.assignObservables(data);
          });
        }
        else {
          // debugger;
          this.assignObservables(data);
        }
    }

    private assignObservables(data) {
      const markers = new Array<MapMarker>();
      markers.push(new MapMarker(data.details.latitude, data.details.longitude,
                     'Call Origin', 'B', './assets/images/icons/phone.png'));
      markers.push.apply(markers, this.getSpecialMapMarkers(data));
      markers.push.apply(markers, this.getRespondersMapMarkers(data));

      Promise.resolve(null).then(() => {
        this.markers$ = of(markers);
        this.direction = of({
          origin: { lat: data.firehall_latitude, lng: data.firehall_longitude },
          destination: { lat: data.details.latitude, lng: data.details.longitude }
        });
      });
    }

    private getRespondersMapMarkers(data): MapMarker [] {
      const markers = new Array<MapMarker>();
      data.callout_details_responding_list.forEach(responder => {
        markers.push(new MapMarker(+responder.latitude, +responder.longitude, responder.user_id,
        '', './assets/images/icons/respond_to_hall.png'));
      });
      return markers;
    }

    private getSpecialMapMarkers(data): MapMarker [] {
      const markers = new Array<MapMarker>();

      // Salmon Valley Water supplies
      markers.push(new MapMarker(54.0919494, -122.5485538, 'GRAVEL PIT (POND) Draft Site Seasonal capacity (30 foot elevation)',
      'W3', './assets/images/icons/water.png'));
      markers.push(new MapMarker(54.0967789, -122.6663365, 'Main Salmon River Draft Site Unlimited capacity (15 foot elevation),',
      'W2', './assets/images/icons/water.png'));
      markers.push(new MapMarker(54.091680,  -122.653749, 'SALMON VALLEY FIRE DEPARTMENT Reservoir capacity: 5 truck loads',
      'W1', './assets/images/icons/water.png'));
      // Shell Glen Water supplies
      markers.push(new MapMarker(53.963694, -122.567766, 'Community Park - 8000L',
      'W3', './assets/images/icons/water.png'));
      markers.push(new MapMarker(53.946556, -122.673760, 'Forman Flats - 10,000 L',
      'W2', './assets/images/icons/water.png'));
      markers.push(new MapMarker(53.965430, -122.592865, 'Shell-Glen Hall Tank 1: 15,000 L Tank 2: 10,000 L',
      'W1', './assets/images/icons/water.png'));

      // Set other RDFFG Firehall locations here
      this.addRegionalFirehall(data, markers, new MapMarker(53.942153, -122.509787, 'FERNDALE FIRE DEPARTMENT',
      '1', './assets/images/icons/firedept.png'));
      this.addRegionalFirehall(data, markers, new MapMarker(53.810381, -122.931133 , 'BEAVERLY FIRE RESCUE',
      '2', './assets/images/icons/firedept.png'));
      this.addRegionalFirehall(data, markers, new MapMarker(53.793184, -122.645770, 'BUCKHORN FIRE DEPARTMENT',
      '3', './assets/images/icons/firedept.png'));
      this.addRegionalFirehall(data, markers, new MapMarker( 54.018618, -123.113643, 'NESS LAKE FIRE DEPARTMENT',
      '4', './assets/images/icons/firedept.png'));
      this.addRegionalFirehall(data, markers, new MapMarker(54.011258, -122.854466, 'PILOT MTN FIRE DEPARTMENT',
      '5', './assets/images/icons/firedept.png'));
      this.addRegionalFirehall(data, markers, new MapMarker(53.846489, -122.631916, 'PINEVIEW / AREA D RESCUE',
      '6', './assets/images/icons/firedept.png'));
      this.addRegionalFirehall(data, markers, new MapMarker(54.0916667, -122.6537361, 'SALMON VALLEY FIRE DEPARTMENT',
      '7', './assets/images/icons/firedept.png'));
      this.addRegionalFirehall(data, markers, new MapMarker(53.9652195, -122.5952811, 'SHELL-GLEN FIRE DEPARTMENT',
      '8', './assets/images/icons/firedept.png'));
      this.addRegionalFirehall(data, markers, new MapMarker(53.692712, -122.670412, 'RED ROCK / STONER FIRE DEPARTMENT',
      '9', './assets/images/icons/firedept.png'));
      this.addRegionalFirehall(data, markers, new MapMarker(53.417887, -122.584593, 'HIXON FIRE DEPARTMENT',
      '10', './assets/images/icons/firedept.png'));
      this.addRegionalFirehall(data, markers, new MapMarker(53.303551, -120.163032, 'MCBRIDE FIRE DEPARTMENT',
      '11', './assets/images/icons/firedept.png'));

      return markers;
    }

    addRegionalFirehall(data, markers: Array<MapMarker>, marker: MapMarker) {
      if (+data.firehall_latitude !== +marker.lat && +data.firehall_longitude !== +marker.lng) {
        markers.push(marker);
      }
    }
    clickedMarker(label: string, index: number) {
      console.log(`clicked the marker: ${label || index}`);
    }
    markerDragEnd(m, $event: MouseEvent) {
      console.log('dragEnd', m, $event);
    }

    isCalloutPending(callout) {
      return !callout.details.callout_status_completed && !callout.details.callout_status_cancelled;
    }

    getDefaultResponseStatus(callout) {
      const defaultResponse = callout.callout_status_defs.filter(function(statusDef) {
        return statusDef.is_responding && statusDef.is_default_response;
      });
      if (defaultResponse != null && defaultResponse.length >= 1) {
        return defaultResponse[0].id;
      }
      return null;
    }

    getNoRespondersList(callout) {
      const isPending = this.isCalloutPending(callout);
      const member_id = callout.member_id;
      const member_access_respond_self = callout.member_access_respond_self;
      const member_access_respond_others = callout.member_access_respond_others;
      const ALLOW_CALLOUT_UPDATES_AFTER_FINISHED = callout.ALLOW_CALLOUT_UPDATES_AFTER_FINISHED;
      const filterList = callout.callout_details_not_responding_list;

      return filterList.filter(function(row_no) {
        if (isPending || ALLOW_CALLOUT_UPDATES_AFTER_FINISHED) {
          return member_id == null || member_id === '' ||
                  (member_access_respond_self && row_no.user_id === member_id) ||
                  (member_access_respond_others && row_no.user_id !== member_id);
        }
        return false;
      });
    }

    getYesRespondersList(callout) {
      const isPending = this.isCalloutPending(callout);
      const member_id = callout.member_id;
      const member_access_respond_self = callout.member_access_respond_self;
      const member_access_respond_others = callout.member_access_respond_others;
      const filterList = callout.callout_details_end_responding_list;

      return filterList.filter(function(row_yes) {
        if (isPending) {
          return member_id == null || member_id === '' ||
                  (member_access_respond_self && row_yes.user_id === member_id) ||
                  (member_access_respond_others && row_yes.user_id !== member_id);
        }
        return false;
      });
    }

    getValidRespondingStatuses(callout_status_defs, member_access, userType) {
      const currentInstance = this;
      return callout_status_defs.filter(function(statusDef) {
        return (statusDef.is_responding || statusDef.is_not_responding || statusDef.is_standby) &&
               currentInstance.hasAccess(member_access, statusDef, currentInstance) &&
               currentInstance.isUserType(+userType, +statusDef);
      });
    }

    private isUserType(userType, statusDef) {
      if (statusDef.userTypes == null) {
          return true;
      }
      const user_type_bit = 1 << userType-1;
      return (statusDef.userTypes != null && (statusDef.userTypes & user_type_bit));
    }

    private hasAccess(userAccess, statusDef, currentInstance) {
      const validateList = currentInstance.getAccessFlagsValidateList();
      if (statusDef.accessFlags != null && validateList.length > 0) {
          if (userAccess != null) {
              let foundMatch = false;
              validateList.forEach(
                function findMatch(access) {
                  if (currentInstance.isFlagSet(+userAccess, +access)) {
                      if (!(currentInstance.isFlagSet(+statusDef.accessFlags, +access))) {
                          // Means all user access flags MUST be set (inclusive)
                          if (statusDef.accessFlagsInclusive == true) {
                              return false;
                          }
                      }
                      else {
                          if (statusDef.accessFlagsInclusive == false) {
                              foundMatch = true;
                              return true;
                          }
                          foundMatch = true;
                      }
                  }
                }
              );
              return foundMatch;
          }
          else {
              return false;
          }
      }
      return true;
    }

    private getAccessFlagsValidateList() {
      const USER_ACCESS_ADMIN = 0x1;
      const USER_ACCESS_SIGNAL_SMS = 0x2;
      const USER_ACCESS_CALLOUT_RESPOND_SELF = 0x4;
      const USER_ACCESS_CALLOUT_RESPOND_OTHERS = 0x8;

      return [USER_ACCESS_ADMIN,
              USER_ACCESS_SIGNAL_SMS,
              USER_ACCESS_CALLOUT_RESPOND_SELF,
              USER_ACCESS_CALLOUT_RESPOND_OTHERS];
    }

    private isFlagSet(flags, flag) {
      return (flags != null && ((flags & flag) === flag));
    }

    private getRootPath(): string {
      return this.location.prepareExternalUrl('../');
    }
}

class MapMarker {
  lat: number;
  lng: number;
  title: string;
  label: string;
  icon: string;
  draggable: boolean;

  constructor(lat, lng, title, label, icon = null, draggable = null) {
    this.lat = lat;
    this.lng = lng;
    this.title = title;
    this.label = label;
    this.icon = icon;
    this.draggable = draggable;
  }
}
