import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Location } from '@angular/common';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { SystemConfigService } from './system-config.service';

@Injectable()
export class GoogleApiService {

  constructor(private http: HttpClient, private location: Location,
    private systemConfigService: SystemConfigService) { }

  getGEOCoordinatesFromAddress(address): Observable<Array<number>> {
    let result_geo_coords = null;
    debugger;
    const config = this.systemConfigService.getSystemConfig();
    const url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' +
                //encodeURI(address) + '&sensor=false&key=' + environment.google_api_key;
                encodeURI(address) + '&sensor=false&key=' + config.google.maps_api_key;
    return this.http.get<any>(url).pipe(
      map(geoloc => {
        //debugger;
        if ( geoloc.results !== undefined &&
             geoloc.results[0] !== undefined &&
             geoloc.results[0].geometry !== undefined &&
             geoloc.results[0].geometry.location !== undefined &&
             geoloc.results[0].geometry.location.lat !== undefined &&
             geoloc.results[0].geometry.location.lng !== undefined) {

          result_geo_coords = new Array( geoloc.results[0].geometry.location.lat,
            geoloc.results[0].geometry.location.lng);
        }
        else {
          console.log('GOOGLE GEO MAP JSON returned an ERROR, response url [' + url + '] result [' + geoloc + ']');
        }
        return result_geo_coords;
    }));
  }

}
