import { Component, OnInit } from '@angular/core';
import { Location } from '@angular/common';
import { Router } from '@angular/router';
import { URLSearchParams } from '@angular/http';
import { LoginService } from '@app/login';
import { LogoffService } from '@app/logoff';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent implements OnInit {

  title = 'Rip Runner';

  constructor(private router: Router, private loginService: LoginService,
              private logoffService: LogoffService) {
  }

  ngOnInit() {
    //debugger;
    if (this.loginService.isLoggedIn()) {
      const params = this.extractParams();
      const page = params.get('page') || '';
      if (page === 'call-details') {
        this.routeToCallDetails(params);
        return;
      }
    }
    this.logoffService.logoff();
  }

  private extractParams() {
    let normalizedQueryString = '';
    if (window.location.search.indexOf('?') === 0) {
      normalizedQueryString = window.location.search.substring(1);
    } else {
      normalizedQueryString = window.location.search;
    }
    return new URLSearchParams(normalizedQueryString);
  }

  private routeToCallDetails(params: URLSearchParams) {
    const cid = +params.get('cid') || 0;
    const ckid = params.get('ckid') || '';
    const member_id = params.get('member_id') || '';
    if (cid > 0) {
      this.router.navigateByUrl(`/common/call-details?cid=${cid}&ckid=${ckid}&member_id=${member_id}`);
    }
  }
}
