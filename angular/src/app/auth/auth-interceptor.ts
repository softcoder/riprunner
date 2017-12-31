import { Injectable, Injector } from '@angular/core';
import { HttpEvent, HttpInterceptor, HttpHandler, HttpRequest } from '@angular/common/http';
import { Observable } from 'rxjs/Observable';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {

  intercept(req: HttpRequest<any>,
            next: HttpHandler): Observable<HttpEvent<any>> {
      //debugger;
      const token = localStorage.getItem('token');
      if (token && !req.url.includes('maps.googleapis.com')) {
          const cloned = req.clone({
              headers: req.headers.set('jwt-token', token)
          });
          return next.handle(cloned);
      }
      else {
          return next.handle(req);
      }
  }
}
