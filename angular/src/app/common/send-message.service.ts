import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Location } from '@angular/common';
import { Observable } from 'rxjs/Observable';

import { AuthService } from '@app/auth';

export class MessageContext {
  type: string;
  msg: string;
  users: string;
}

@Injectable()
export class SendMessageService {

  constructor(private http: HttpClient, private location: Location,
              private authService: AuthService) {
  }

  send(msgContext: MessageContext): Promise<any> {
    debugger;
    const fhid = this.authService.getFirehallId();
    const href = this.location.prepareExternalUrl('../angular-services/send-message-service.php');
    const requestUrl = `${href}/send?fhid=${fhid}`;
    return this.http.post(requestUrl, msgContext).toPromise()
    .then(response => {
        debugger;
        return response;
    })
    .catch((err) => {
      debugger;
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
