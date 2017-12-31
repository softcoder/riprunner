import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { RouterModule, Routes } from '@angular/router'
import { NgxPermissionsModule, NgxPermissionsGuard } from 'ngx-permissions';

import { MenuCalloutHistoryComponent } from './menu-callout-history.component';
import { MenuCalloutHistoryService } from './menu-callout-history.service';
import { SystemSharedModule } from '@app/common';

const menuRoutes: Routes = [
  { path: '', component: MenuCalloutHistoryComponent,
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
    MenuCalloutHistoryService
  ],
  declarations: [
    MenuCalloutHistoryComponent,
  ]
})

export class MenuCalloutHistoryModule { }
