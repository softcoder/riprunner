import {Component, AfterViewInit, ViewChild} from '@angular/core';
import {MatPaginator, MatSort, MatTableDataSource} from '@angular/material';
import {Observable} from 'rxjs/Observable';
import {merge} from 'rxjs/observable/merge';
import {of as observableOf} from 'rxjs/observable/of';
import {catchError} from 'rxjs/operators/catchError';
import {map} from 'rxjs/operators/map';
import {startWith} from 'rxjs/operators/startWith';
import {switchMap} from 'rxjs/operators/switchMap';
import {Location} from '@angular/common';
import { Router } from '@angular/router';

import { AuthService } from '@app/auth';
import { MenuCalloutHistoryService, CalloutHistoryItem } from './menu-callout-history.service';

@Component({
  selector: 'app-menu-callout-history',
  templateUrl: './menu-callout-history.component.html',
  styleUrls: ['./menu-callout-history.component.css']
})
export class MenuCalloutHistoryComponent implements AfterViewInit {

  displayedColumns: Array<string>;
  dataSource = new MatTableDataSource();

  resultsLength = 0;
  isLoadingResults: Observable<boolean> = Observable.of(false);

  @ViewChild(MatPaginator) paginator: MatPaginator;
  @ViewChild(MatSort) sort: MatSort;

  constructor(private location: Location, private authService: AuthService,
              private calloutHistoryService: MenuCalloutHistoryService, private router: Router) {
    //debugger;
    if (this.authService.hasPermission('ROLE-admin')) {
      this.displayedColumns = ['id', 'calltime', 'calltype', 'address', 'latitude', 'longitude',
                        'units', 'status', 'updatetime', 'call_key', 'responders', 'hours_spent',
                        'call_details', 'override_address'
                      ];
    } else {
      this.displayedColumns = ['id', 'calltime', 'calltype', 'address', 'latitude', 'longitude', 
                        'units', 'status', 'updatetime', 'call_key', 'responders', 'hours_spent',
                        'call_details'
                      ];
    }
  }

  ngAfterViewInit() {
    // If the user changes the sort order, reset back to the first page.
    this.sort.sortChange.subscribe(() => this.paginator.pageIndex = 0);

    merge(this.sort.sortChange, this.paginator.page)
      .pipe(
        startWith({}),
        switchMap(() => {
          Promise.resolve(null).then(() => this.isLoadingResults = Observable.of(true));
          return this.calloutHistoryService.getCalloutHistory(
            this.sort.active, this.sort.direction, this.paginator.pageIndex);
        }),
        map(data => {
          // Flip flag to show that loading has finished.
          Promise.resolve(null).then(() =>
            this.isLoadingResults = Observable.of(false)
          );
          this.resultsLength = data.length;
          return data;
        }),
        catchError((err) => {
          debugger;

          console.log('Error getting grid data: ' + err);
          Promise.resolve(null).then(() =>
            this.isLoadingResults = Observable.of(false)
          );
          return observableOf([]);
        })
      ).subscribe(data => {
        this.dataSource.data = data;
        this.dataSource.sort = this.sort;
        this.dataSource.paginator = this.paginator;
        }
      );
  }

  reloadData() {
    this.ngAfterViewInit();
  }
  getFirehallId(): string {
    return this.authService.getFirehallId();
  }
  getExternalUrl(url: string, handOffJWT: boolean = true): string {
    //debugger;
    url = this.getRootPath() + url;
    return this.authService.injectJWTtoken(url, handOffJWT);
  }
  private getRootPath(): string {
    return this.location.prepareExternalUrl('../');
  }
}
