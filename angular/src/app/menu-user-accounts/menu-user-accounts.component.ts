import {Component, AfterViewInit, ElementRef, ViewChild} from '@angular/core';
import {MatDialog, MatPaginator, MatSort, MatTableDataSource} from '@angular/material';
import {Observable, merge, of} from 'rxjs';
import {catchError, map, startWith, switchMap} from 'rxjs/operators';
import {Location} from '@angular/common';
import {Router} from '@angular/router';

import {AuthService} from '@app/auth';
import {UserAccountsService, UserAccount, UserAccountType} from './user-accounts.service';
import {DeleteDialogComponent} from '@app/common';
import {SendMessageComponent} from '@app/common';

@Component({
  selector: 'app-menu-user-accounts',
  templateUrl: './menu-user-accounts.component.html',
  styleUrls: ['./menu-user-accounts.component.css'],
})
export class MenuUserAccountsComponent implements AfterViewInit {

  static NEW_USER_ID = '-1';
  errorMessage: string;
  displayedColumns: Array<string>;
  dataSource = new MatTableDataSource();
  selectedUsers: Array<string> = new Array<string>();

  resultsLength = 0;
  isLoadingResults: Observable<boolean> = of(false);

  userAccountTypes: Array<UserAccountType>;
  editRowId: string = null;
  new_password_1: string;
  new_password_2: string;
  selfEditMode: boolean;

  @ViewChild(MatPaginator,{static:false}) paginator: MatPaginator;
  @ViewChild(MatSort,{static:false}) sort: MatSort;
  @ViewChild('selectAllUsers',{static:false}) public selectAllUsers: ElementRef;

  constructor(private location: Location, private authService: AuthService,
              private userAccountService: UserAccountsService,
              private router: Router, public dialog: MatDialog) {
    // debugger;
    const params = this.extractParams();
    this.selfEditMode = ((params.get('se') || '') !== '' ? true : false);
    this.setDisplayedColumns();
  }

  ngAfterViewInit() {
    // debugger;
    if (this.sort === undefined) {
      setTimeout(() => { this.ngAfterViewInit(); }, 25);
      return;
    }
    // If the user changes the sort order, reset back to the first page.
    this.sort.sortChange.subscribe(() => this.paginator.pageIndex = 0);

    merge(this.sort.sortChange, this.paginator.page)
      .pipe(
        startWith({}),
        switchMap(() => {
          Promise.resolve(null).then(() => this.isLoadingResults = of(true));
          return this.userAccountService.getUserAccounts(
            this.sort.active, this.sort.direction, this.paginator.pageIndex);
        }),
        map(data => {
          // Flip flag to show that loading has finished.
          Promise.resolve(null).then(() =>
            this.isLoadingResults = of(false)
          );
          this.resultsLength = data.length;
          return data;
        }),
        catchError((err) => {
          debugger;

          this.errorMessage = err.error.text;
          console.log('Error getting grid data: ' + err.error.text);
          Promise.resolve(null).then(() =>
            this.isLoadingResults = of(false)
          );
          return of([]);
        })
      ).subscribe(data => {
        // debugger;
        this.dataSource.data = data;
        if (this.selfEditMode) {
          this.dataSource.filterPredicate = (tbldata: UserAccount, filter: string) => String(tbldata.id) === filter;
          this.applyFilter(this.authService.getId());
        }

        this.dataSource.sort = this.sort;
        this.dataSource.paginator = this.paginator;
        }
      );
  }

  reloadData() {
    this.errorMessage = '';
    this.ngAfterViewInit();
  }
  getFirehallId(): string {
    return this.authService.getFirehallId();
  }
  getExternalUrl(url: string, handOffJWT: boolean = true): string {
    // debugger;
    url = this.getRootPath() + url;
    return this.authService.injectJWTtoken(url, handOffJWT);
  }
  getUserAccountType(user: UserAccount) {
    // debugger;
    if (this.userAccountTypes == null) {
      this.userAccountService.getUserAccountTypes().
      subscribe(data => {
        // debugger;
        this.userAccountTypes = data;
        return this.findUserAccountType(user, this.userAccountTypes);
      });
    }
    else {
      // debugger;
      return this.findUserAccountType(user, this.userAccountTypes);
    }
  }

  getUserAccountTypes() {
    // debugger;
    if (this.userAccountTypes == null) {
      this.userAccountService.getUserAccountTypes().
      subscribe(data => {
        // debugger;
        this.userAccountTypes = data;
        return this.userAccountTypes;
      });
    }
    else {
      // debugger;
      return this.userAccountTypes;
    }
  }

  isEditing(): boolean {
    // debugger;
    return this.editRowId !== null;
  }

  editingRow(rowid): boolean {
    // debugger;
    return this.editRowId !== null && this.editRowId == rowid ? true : false;
  }

  add_user() {
    // debugger;
    this.new_password_1 = '';
    this.new_password_2 = '';

    this.editRowId = MenuUserAccountsComponent.NEW_USER_ID;

    this.dataSource.data.unshift({
      id: this.editRowId,
      firehall_id: null,
      user_id: '',
      new_password_1: this.new_password_1,
      new_password_2: this.new_password_2,
      email: '',
      user_type: null,
      mobile_phone: null,
      access_admin: false,
      access_sms: false,
      access_respond_self: false,
      access_respond_others: false,
      active: true,
      twofa: false
    });
    this.setDisplayedColumns();
    this.dataSource._updateChangeSubscription();
  }

  edit_user(row) {
    // debugger;
    this.new_password_1 = '';
    this.new_password_2 = '';
    this.editRowId = row.id;
    this.setDisplayedColumns();
  }

  delete_user(row) {
    // debugger;

/*
    if (confirm('Confirm DELETE for user: ' + row.user_id + '?')) {
      this.userAccountService.deleteUserAccount(row.id).subscribe(result => {
        debugger;
        this.editRowId = null;
        this.setDisplayedColumns();
        this.reloadData();
      });
    }
*/
    const dialogRef = this.dialog.open(DeleteDialogComponent, {
      data: { id: row.id, title: 'Delete the user: [' + row.user_id + '] ?',
              context: 'deleteUserAccount', service: this.userAccountService },
    });

    dialogRef.afterClosed().subscribe(result => {
      // debugger;
      if (result === 1) {
        this.editRowId = null;
        this.setDisplayedColumns();
        this.reloadData();
      }
    });
  }

  cancel_user(event, row) {
    // debugger;
    const isNewUser = row.id == MenuUserAccountsComponent.NEW_USER_ID;
    this.errorMessage = '';
    this.new_password_1 = '';
    this.new_password_2 = '';
    this.editRowId = null;
    if (isNewUser) {
      this.dataSource.data.splice(0, 1);
    }
    this.setDisplayedColumns();
    this.dataSource._updateChangeSubscription();
  }

  save_user(event, row) {
    // debugger;
    this.save_user_edit(row).then(result => {
      // debugger;
      if (result === 'ok') {
        this.errorMessage = '';
        this.editRowId = null;
        this.setDisplayedColumns();
        this.reloadData();
      }
      else {
        this.errorMessage = result;
      }
    });
  }

  save_user_edit(row) {
    // debugger;
    if (row.id < 0) {
      return this.userAccountService.addUserAccount(this.new_password_1, this.new_password_2, row);
    }
    return this.userAccountService.editUserAccount(this.new_password_1, this.new_password_2, row);
  }

  getSelectedUsers() {
    // debugger;
    return this.selectedUsers.join(',');
  }

  select_all_users($event) {
    // debugger;
    this.selectedUsers.length = 0;
    if ($event.target.checked) {
      this.dataSource.data.forEach( element => {
        // debugger;
        this.selectedUsers.push(element['id']);
      });
    }
  }

  check_user_selected(row) {
    // debugger;
    if (this.selectedUsers.includes(row['id'])) {
      return true;
    }
    return false;
  }

  select_user($event, row) {
    // debugger;
    this.selectAllUsers.nativeElement.checked = false;
    if ($event.target.checked) {
        if (!this.selectedUsers.includes(row['id'])) {
          this.selectedUsers.push(row['id']);
        }
    }
    else {
      // delete this.selectedUsers[row['id']];
      const index: number = this.selectedUsers.indexOf(row['id']);
      if (index !== -1) {
          this.selectedUsers.splice(index, 1);
      }
    }
  }

  private setDisplayedColumns() {
    if (this.isEditing()) {
      this.displayedColumns = ['grid_update_row', 'id', 'firehall_id', 'user_id', 'new_password_1', 'new_password_2',
                'email', 'user_type', 'mobile_phone', 'access_admin', 'access_sms', 'access_respond_self',
                'access_respond_others', 'active', 'twofa', 'updatetime'
           ];
    }
    else {
      this.displayedColumns = ['grid_update_row', 'id', 'firehall_id', 'user_id',
                'email', 'user_type', 'mobile_phone', 'access_admin', 'access_sms', 'access_respond_self',
                'access_respond_others', 'active', 'twofa', 'updatetime'
           ];
    }
  }

  private findUserAccountType(user: UserAccount, accountTypes: Array<UserAccountType>): UserAccountType {
    // debugger;
    const itemFound = accountTypes.filter(function(accountType) {
      if (user != null && user.user_type === accountType.id) {
        return true;
      }
      return false;
    });
    if (itemFound != null && itemFound.length >= 1) {
      return itemFound[0];
    }
    // debugger;
    return null;
  }

  private getRootPath(): string {
    return this.location.prepareExternalUrl('../');
  }

  private extractParams() {
    let normalizedQueryString = '';
    if (window.location.search.indexOf('?') === 0) {
      normalizedQueryString = window.location.search.substring(1);
    } else {
      normalizedQueryString = window.location.search;
    }
    return new URLSearchParams(normalizedQueryString);
  }

  private applyFilter(filterValue: string) {
    filterValue = filterValue.trim(); // Remove whitespace
    filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
    this.dataSource.filter = filterValue;
  }
}
