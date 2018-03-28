import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Location } from '@angular/common';
import { Observable } from 'rxjs/Observable';

import { AuthService } from '@app/auth';
import { DeleteDataService } from '@app/common';

export interface UserAccount {
  id: number;
  firehall_id: string;
  user_id: string;
  mobile_phone: string;
  access: number;
  user_type: number;
  active: boolean;
  email: string;
  updatetime: string;
  access_admin: boolean;
  access_sms: boolean;
  access_respond_self: boolean;
  access_respond_others: boolean;
}

export interface UserAccountType {
  id: number;
  firehall_id: string;
  name: string;
  default_access: number;
  updatetime: string;
}

@Injectable()
export class UserAccountsService implements DeleteDataService {

  constructor(private http: HttpClient, private location: Location, private authService: AuthService) {
  }

  delete(data: any): Promise<any> {
    // debugger;
    if (data.context === 'deleteUserAccount') {
      return this.deleteUserAccount(data.id);
    }
  }

  getUserAccounts(sort: string, order: string, page: number): Observable<UserAccount[]> {
    const fhid = this.authService.getFirehallId();
    const href = this.location.prepareExternalUrl('../angular-services/user-accounts-service.php');
    const requestUrl = `${href}/users?fhid=${fhid}`;
    return this.http.get<UserAccount[]>(requestUrl);
  }

  getUserAccountTypes(): Observable<UserAccountType[]> {
    const fhid = this.authService.getFirehallId();
    const href = this.location.prepareExternalUrl('../angular-services/user-accounts-service.php');
    const requestUrl = `${href}/user_types?fhid=${fhid}`;
    return this.http.get<UserAccountType[]>(requestUrl);
  }

  deleteUserAccount(user_id: number): Promise<any> {
    // debugger;
    const fhid = this.authService.getFirehallId();
    const href = this.location.prepareExternalUrl('../angular-services/user-accounts-service.php');
    const requestUrl = `${href}/delete_user?fhid=${fhid}&user_id=${user_id}`;
    return this.http.post(requestUrl, null).toPromise()
    .then(response => {
        // debugger;
        return response;
    })
    .catch((err) => {
      debugger;
      return this.handleErrorPromise(err);
    });
  }

  addUserAccount(password1: string, password2: string, user: UserAccount): Promise<any> {
    const fhid = this.authService.getFirehallId();
    const href = this.location.prepareExternalUrl('../angular-services/user-accounts-service.php');
    const requestUrl = `${href}/add_user?password1=${password1}&password2=${password2}`;
    return this.http.post(requestUrl, user).toPromise()
    .then(response => {
        //debugger;
        return response;
    })
    .catch((err) => {
      //debugger;
      return this.handleErrorPromise(err);
    });
  }

  editUserAccount(password1: string, password2: string, user: UserAccount): Promise<any> {
    const fhid = this.authService.getFirehallId();
    const href = this.location.prepareExternalUrl('../angular-services/user-accounts-service.php');
    const requestUrl = `${href}/edit_user?password1=${password1}&password2=${password2}`;
    return this.http.post(requestUrl, user).toPromise()
    .then(response => {
        //debugger;
        return response;
    })
    .catch((err) => {
      //debugger;
      return this.handleErrorPromise(err);
    });
  }

  private handleErrorPromise(error: Response | any) {
    debugger;

    console.error(error.message || error);
    // if (error.status === 400) {
    //  return 'Invalid login credentials.';
    // }
    if (error.error && error.error.exception) {
      return error.error.exception.message;
    }
    if (error.error && error.error.text) {
      return error.error.text;
    }
    return (error.message || error);
  }

}
