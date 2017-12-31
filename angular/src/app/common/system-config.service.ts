import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Location } from '@angular/common';
import { Observable } from 'rxjs/Observable';

@Injectable()
export class SystemConfigService {

    url_action = '/../angular-services/system-config-service.php/config';
    url = '';

    constructor(private http: HttpClient, private location: Location) {
      // debugger;
      this.url = this.location.prepareExternalUrl(this.url_action);
      // console.log('BaseHref: '+this.url);
    }

    getSystemConfig(): Observable<any> {
      return this.http.get<any>(this.url);
    }
}
