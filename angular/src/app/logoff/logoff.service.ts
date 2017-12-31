import { Injectable } from '@angular/core';

import { AuthService } from '@app/auth';

@Injectable()
export class LogoffService {

    constructor(private authService: AuthService) {
    }

    logoff() {
      //debugger;
      return this.authService.logoff();
    }
}
