import { Component, OnInit } from '@angular/core';
import { Observable ,  of } from 'rxjs';
import { Router, Params} from '@angular/router';

import { LoginService } from './login.service';
import { Login } from './login';
import { LogoffModule, LogoffService } from '@app/logoff';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css']
})
export class LoginComponent implements OnInit {

  login: Login = new Login();
  errorMessage: string;
  isLoadingResults: Observable<boolean> = of(false);
  twoFAkeyRequired = false;

  private sub: any;

  constructor(private loginService: LoginService,
              private logoffService: LogoffService) {
  }

  ngOnInit() {
      //debugger;
      if (this.loginService.isLoggedIn()) {
        this.logoffUser();
      }
      const params = this.extractParams();
      const fhid = params.get('fhid') || '';
      if (fhid !== '') {
        this.login.fhid = fhid;
      }
  }

  private extractParams() {
    let normalizedQueryString = '';
    if (window.location.search.indexOf('?') === 0) {
      normalizedQueryString = window.location.search.substring(1);
    } else {
      normalizedQueryString = window.location.search;
    }
    console.log('Login params: ' + normalizedQueryString);
    return new URLSearchParams(normalizedQueryString);
  }

  loginUser() {
    // debugger;
    Promise.resolve(null).then(() => this.isLoadingResults = of(true));
    this.loginService.login(this.login).then(loginResult => {
      // debugger;
      Promise.resolve(null).then(() => this.isLoadingResults = of(false));
      this.errorMessage = loginResult;
      if (this.loginService.getTwoFARequired()) {
        this.twoFAkeyRequired = true;
        this.login.twofaKey = '1';
      }
    })
    .catch((err) => {
      debugger;
      Promise.resolve(null).then(() => this.isLoadingResults = of(false));
      this.errorMessage = err;
    });
  }

  logoffUser() {
    Promise.resolve(null).then(() => {

    this.isLoadingResults = of(true);
    this.logoffService.logoff();
    this.isLoadingResults = of(false);

    this.errorMessage = '';
    this.login = new Login();
    });
  }
}
