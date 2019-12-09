import { Injectable, Injector } from '@angular/core';
import { HttpEvent, HttpInterceptor, HttpHandler, HttpRequest, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {

  intercept(req: HttpRequest<any>,
            next: HttpHandler): Observable<HttpEvent<any>> {
      //debugger;
      const token = localStorage.getItem('token');
      const refreshToken = localStorage.getItem('refreshToken');
      if (token && !req.url.includes('maps.googleapis.com') &&
      !req.url.includes('mapapiprxy')) {
          const reqWithInjection = req.clone({
            headers: new HttpHeaders({
                'jwt-token': token,
                'jwt-refresh-token': refreshToken
            })
          });
          //console.log('Intercepted HTTP call', reqWithInjection);
          return next.handle(reqWithInjection);
      }
      else {
          return next.handle(req);
      }
  }
}
