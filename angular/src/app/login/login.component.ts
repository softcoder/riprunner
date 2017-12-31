import { Component, OnInit } from '@angular/core';
import { Observable } from 'rxjs/Observable';
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
  isLoadingResults: Observable<boolean> = Observable.of(false);

  private sub: any;

  constructor(private loginService: LoginService,
              private logoffService: LogoffService) {
  }

  ngOnInit() {
      //debugger;
      if (this.loginService.isLoggedIn()) {
        this.logoffUser();
      }
  }

  loginUser() {
    //debugger;
    Promise.resolve(null).then(() => this.isLoadingResults = Observable.of(true));
    this.loginService.login(this.login).then(loginResult => {
      //debugger;
      Promise.resolve(null).then(() => this.isLoadingResults = Observable.of(false));
      this.errorMessage = loginResult;
    })
    .catch((err) => {
      debugger;
      Promise.resolve(null).then(() => this.isLoadingResults = Observable.of(false));
      this.errorMessage = err;
    });
  }

  logoffUser() {
    Promise.resolve(null).then(() => {

    this.isLoadingResults = Observable.of(true);
    this.logoffService.logoff();
    this.isLoadingResults = Observable.of(false);

    this.errorMessage = '';
    this.login = new Login();
    });
  }
}
