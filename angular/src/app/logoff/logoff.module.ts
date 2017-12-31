import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { RouterModule, Routes } from '@angular/router'
import { NgxPermissionsModule, NgxPermissionsGuard } from 'ngx-permissions';

import { LogoffService } from './logoff.service';
//import { SystemSharedModule } from '@app/common';

const menuRoutes: Routes = [
  { path: '', redirectTo: '/login' },
]

@NgModule({
  imports: [
    CommonModule,
    NgxPermissionsModule.forChild(),
    RouterModule.forChild(menuRoutes),
    //SystemSharedModule,
  ],
  providers: [
    LogoffService
  ],
  declarations: [
  ]
})
export class LogoffModule { }
