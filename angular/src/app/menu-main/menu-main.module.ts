import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { NgxPermissionsModule, NgxPermissionsGuard } from 'ngx-permissions';

import { MenuMainComponent } from './menu-main.component';
import { SystemSharedModule } from '@app/common';

const menuRoutes: Routes = [
  { path: '', component: MenuMainComponent,
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
    MenuMainComponent
  ]
})
export class MenuMainModule { }
