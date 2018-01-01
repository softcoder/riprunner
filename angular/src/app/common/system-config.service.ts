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

    getSystemConfig() {
      //debugger;

      const config = localStorage.getItem('rr-config');
      if (config !== null) {
        console.log('getSystemConfig() returing cached result.');
        return JSON.parse(config);
      }
      const promise = new Promise((resolve, reject) => {
        return this.http.get<any>(this.url).toPromise()
        .then(data => {
          localStorage.setItem('rr-config', JSON.stringify(data));
          return data;
        });
      });
      return promise;
    }
}
