import { BrowserModule } from '@angular/platform-browser';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { NgxPermissionsModule, NgxPermissionsGuard } from 'ngx-permissions';

import { AppComponent } from './app.component';
import { AuthInterceptor } from '@app/auth'
import { SystemSharedModule } from '@app/common';
import { LogoffModule, LogoffService } from '@app/logoff';
import { LoginComponent } from '@app/login';
import { LoginService } from '@app/login';


const appRoutes: Routes = [
  { path: 'login', component: LoginComponent },
  { path: 'main-menu', loadChildren: './menu-main/menu-main.module#MenuMainModule' },
  { path: 'call-history-menu', loadChildren: './menu-callout-history/menu-callout-history.module#MenuCalloutHistoryModule' },
  { path: 'call-monitor-menu', loadChildren: './menu-callout-monitor/menu-callout-monitor.module#MenuCalloutMonitorModule' },
  { path: 'common', loadChildren: './common/system-shared.module#SystemSharedModule' },
  { path: '', redirectTo: '/login', pathMatch: 'full' },
  { path: '**', redirectTo: '/login', pathMatch: 'full' },
];

@NgModule({
  declarations: [
    AppComponent,
    LoginComponent,
  ],
  imports: [
    BrowserModule,
    BrowserAnimationsModule,
    FormsModule,
    HttpClientModule,
    RouterModule.forRoot(appRoutes),
    NgxPermissionsModule.forRoot(),
    SystemSharedModule.forRoot(),
  ],
  providers: [
    {
      provide: HTTP_INTERCEPTORS,
      useClass: AuthInterceptor,
      multi: true
    },
    LoginService, LogoffService
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
