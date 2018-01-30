import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { NgxPermissionsModule, NgxPermissionsGuard } from 'ngx-permissions';

import { MenuCalloutMonitorComponent } from './menu-callout-monitor.component';
import { SystemSharedModule } from '@app/common';

const menuRoutes: Routes = [
  { path: '', component: MenuCalloutMonitorComponent,
    canActivate: [NgxPermissionsGuard],
      data: {
        permissions: {
          only: 'USER-AUTHENTICATED',
          redirectTo: '/login'
        }
      }
  },
];

@NgModule({
  imports: [
    CommonModule,
    NgxPermissionsModule.forChild(),
    RouterModule.forChild(menuRoutes),
    SystemSharedModule,
  ],
  providers: [
  ],
  declarations: [
    MenuCalloutMonitorComponent
  ]
})
export class MenuCalloutMonitorModule { }
