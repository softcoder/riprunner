import { Component, OnInit } from '@angular/core';
import { Location } from '@angular/common';
import { Observable } from 'rxjs/Observable';

import { AuthService } from '@app/auth';
import { SystemConfigService } from '@app/common';

@Component({
  selector: 'app-menu-main',
  templateUrl: './menu-main.component.html',
  styleUrls: ['./menu-main.component.css']
})
export class MenuMainComponent implements OnInit {

  public systemConfig: Observable<any>;

  constructor(private location: Location, private authService: AuthService,
    private systemConfigService: SystemConfigService) {
  }

  ngOnInit() {
      // debugger;
      this.systemConfig = this.systemConfigService.getSystemConfig();
  }

  getExternalUrl(url: string, handOffJWT: boolean = true): string {
    // debugger;
    url = this.getRootPath() + url;
    return this.authService.injectJWTtoken(url, handOffJWT);
  }

  private getRootPath(): string {
    return this.location.prepareExternalUrl('../');
  }
}
