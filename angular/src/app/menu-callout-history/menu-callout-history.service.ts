import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Location } from '@angular/common';
import { Observable } from 'rxjs/Observable';

import { AuthService } from '@app/auth';

export interface CalloutHistoryItem {
  id: number;
  calltime: string;
  calltype: string;
  address: string;
  latitude: number;
  longitude: number;
  units: string;
  updatetime: string;
  status: number;
  call_key: string;
  responders: number;
  hours_spent: number;
  callout_type_desc: string;
  callout_address_origin: string;
  callout_address_dest: string;
  callout_status_desc: string;
}

@Injectable()
export class MenuCalloutHistoryService {

  constructor(private http: HttpClient, private location: Location, private authService: AuthService) {
  }

  getCalloutHistory(sort: string, order: string, page: number): Observable<CalloutHistoryItem[]> {
    var fhid = this.authService.getFirehallId();
    const href = this.location.prepareExternalUrl("../angular-services/menu-callout-history-service.php");
    const requestUrl = `${href}/history?fhid=${fhid}`;
    return this.http.get<CalloutHistoryItem[]>(requestUrl);
  }
}
