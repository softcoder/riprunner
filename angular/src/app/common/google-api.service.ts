import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Location } from '@angular/common';
import { Observable } from 'rxjs/Observable';

@Injectable()
export class GoogleApiService {

  constructor(private http: HttpClient, private location: Location) { }

  getGEOCoordinatesFromAddress(address): Observable<Array<number>> {
    let result_geo_coords = null;
    const url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' + encodeURI(address) + '&sensor=false';
    return this.http.get<any>(url).
      map(geoloc => {
        // debugger;
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
          console.log('GEO MAP JSON response error google geo api url [$url] result [$result]');
        }
        return result_geo_coords;
    });
  }

}
