import { Injectable } from '@angular/core';
import * as moment from "moment";
import { HttpClient } from '@angular/common/http';
import { Location } from '@angular/common';
import { Router } from '@angular/router';

import { NgxPermissionsService, NgxRolesService } from 'ngx-permissions';
import * as JWT from 'jwt-decode';

import { User } from './user';

interface AuthResponse {
  status: boolean;
  expiresIn: number;
  user: string;
  message: string;
  token: string;
}

interface TokenObject {
  id: number;
  acl: string;
  fhid: string;
  uid: string;
  fcmTokens?: { [token: string]: true };
}

@Injectable()
export class AuthService {
    url_action = '/../process_login.php';
    url = '';
    lastErrorMsg = '';

    logoff_url_action = '/../logout.php';
    logoff_url = '';

    constructor(private http: HttpClient, private location: Location,
      private router: Router, private permissionsService: NgxPermissionsService) {
      // debugger;

      this.url = this.location.prepareExternalUrl(this.url_action);
      this.logoff_url = this.location.prepareExternalUrl(this.logoff_url_action);
      // console.log('BaseHref: '+this.url);

      // this.logout();
    }

    login(user: User): Promise<boolean> {
      // debugger;

      this.logout();
      this.lastErrorMsg = '';
      return this.http.post<AuthResponse>(this.url, user)
        .toPromise()
        .then(response => {
            // debugger;
            if (response && response.status && response.token) {
              this.setSession(response);
              return true;
            }
            debugger;
            this.lastErrorMsg = response.message;
            return false;
        })
        .catch((err) => {
          debugger;
          this.lastErrorMsg = this.handleErrorPromise(err);
          return false;
        });
    }

    public logoff() {
      // debugger;

      this.http.post(this.logoff_url, {})
        .subscribe(res => {
          // console.log("Logout response: " + res);
        },
        err => {
          console.log('Logout Error occured: ' + err);
        }
      );
      this.logout();
      this.lastErrorMsg = '';
    }

    public getFirehallId(): string {
      if (this.isLoggedIn()) {
        return localStorage.getItem('fhid');
      }
      return null;
    }

    public getId(): string {
      if (this.isLoggedIn()) {
        return localStorage.getItem('id');
      }
      return null;
    }

    public getLastErrorMsg(): string {
      return this.lastErrorMsg;
    }

    public injectJWTtoken(url: string, handOffJWT: boolean = false): string {
      // debugger;

      if (this.isLoggedIn() && url.indexOf('JWT_TOKEN') === -1) {
        const token = localStorage.getItem('token');
        url = this.addQueryParam(url, 'JWT_TOKEN', token);
        if (handOffJWT) {
          url = this.addQueryParam(url, 'JWT_TOKEN_HANDOFF', 'true');
        }
      }
      return url;
    }

    public hasPermission(permission: string | string[]): Promise<boolean> {
        return this.permissionsService.hasPermission(permission);
    }

    private handleErrorPromise(error: Response | any) {
      debugger;

      console.error(error.message || error);
      if (error.status === 401) {
        return 'Invalid login credentials.';
      }
      return (error.message || error);
    }

    private setSession(authResult) {
        // debugger;

        const permissions: Array<string> = [ 'USER-AUTHENTICATED' ];
        localStorage.setItem('token', authResult.token);
        const jwtToken: TokenObject = JWT(authResult.token);
        if (jwtToken && jwtToken.acl && jwtToken.acl !== '') {
          const acl = JSON.parse(jwtToken.acl);
          localStorage.setItem('acl', acl);
          permissions.push('ROLE-' + acl.role);
          localStorage.setItem('fhid', jwtToken.fhid);
          localStorage.setItem('id', String(jwtToken.id));
        }
        const expiresAt = moment().add(authResult.expiresIn, 'second');
        localStorage.setItem('expires_at', JSON.stringify(expiresAt.valueOf()) );

        this.permissionsService.loadPermissions(permissions);
    }

    private logout() {
        // debugger;

        localStorage.removeItem('token');
        localStorage.removeItem('acl');
        localStorage.removeItem('expires_at');
        localStorage.removeItem('fhid');
        localStorage.removeItem('id');
        localStorage.removeItem('rr-config');
        this.permissionsService.loadPermissions([]);
    }

    private addQueryParam(url: string, name: string, value: string): string {
        if (url.indexOf('?') === -1) {
          url += '?';
        }
        else {
          url += '&';
        }
        url += (name + '=' + value);
        return url;
    }

    public isLoggedIn() {
        // debugger;
        const loginToken = localStorage.getItem('token');
        const isNotExpired = moment().isBefore(this.getExpiration());
        if (loginToken != null && isNotExpired === false) {
          console.log('User Auth expired!');
        }
        return loginToken != null && isNotExpired;
    }

    isLoggedOut() {
        return !this.isLoggedIn();
    }

    getExpiration() {
        const expiration = localStorage.getItem('expires_at');
        const expiresAt = JSON.parse(expiration);
        return moment(expiresAt);
    }
}
