import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Location } from '@angular/common';
import { Observable } from 'rxjs/Observable';

import { AuthService } from '@app/auth';

export interface CalloutDetailsItem {
  id: number;
  calltime: string;
  calltype: string;
  address: string;
  latitude: number;
  longitude: number;
  units: string;
  updatetime: string;
  status: number;
  call_key: string;
  callout_comments: string;
  callout_type_desc: string;
  callout_status_desc: string;
  callout_status_completed: string;
  callout_status_cancelled: string;
  callout_status_entity: Array<any>;
  callout_address_dest: string;
  callout_geo_dest: string;
}

@Injectable()
export class CalloutDetailsService {
  constructor(private http: HttpClient, private location: Location, private authService: AuthService) {
  }

  getDetails(cid, ckid, member_id): Observable<any> {
    const fhid = this.authService.getFirehallId();
    const href = this.location.prepareExternalUrl('../angular-services/callout-details-service.php');
    const requestUrl = `${href}/details?fhid=${fhid}&cid=${cid}&ckid=${ckid}&member_id=${member_id}`;
    return this.http.get<CalloutDetailsItem>(requestUrl);
  }

  updateResponse(cid, ckid, member_id, responder_id, status_id) {
    // debugger;

    const fhid = this.authService.getFirehallId();
    const href = this.location.prepareExternalUrl('../cr');
    let requestUrl = `${href}/fhid=${fhid}&cid=${cid}&ckid=${ckid}&uid=${responder_id}&status=${status_id}`;
    if (member_id !== null) {
      requestUrl += `&member_id=${member_id}`;
    }
    return this.http.get(requestUrl, { responseType: 'text' });
  }
}
