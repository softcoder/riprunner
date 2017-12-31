import { Injectable } from '@angular/core';
import { Router } from '@angular/router';

import { AuthService } from '@app/auth';
import { Login } from './login';
import { User } from '@app/auth';

@Injectable()
export class LoginService {

    url_action_forward = '/main-menu';

    constructor(private router: Router, private authService: AuthService) {
    }

    public isLoggedIn() {
      return this.authService.isLoggedIn();
    }

    login(login: Login): Promise<string> {
        // debugger;

        const enc_pwd: string = btoa(login.password);
        login.password = '';
        const user: User = this.extractUser(login, enc_pwd);
        login = new Login();

        return this.authService.login(user).
          then(loginResult => {
            // debugger;
            if (loginResult) {
              this.router.navigateByUrl(this.url_action_forward);
              return '';
            }
            return this.authService.getLastErrorMsg();
        })
        .catch((err) => {
          debugger;
          return err;
        });
    }

    private extractUser(login: Login, enc_pwd: string): User {
        const user: User = new User();
        user.fhid = login.fhid;
        user.username = login. username;
        user.password = login.password;
        user.p = enc_pwd;
        return user;
    }
}
