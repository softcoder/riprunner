import { NgModule, ModuleWithProviders, Injectable } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  MatAutocompleteModule,
  MatButtonModule,
  MatButtonToggleModule,
  MatCardModule,
  MatCheckboxModule,
  MatChipsModule,
  MatDatepickerModule,
  MatDialogModule,
  MatExpansionModule,
  MatGridListModule,
  MatIconModule,
  MatInputModule,
  MatListModule,
  MatMenuModule,
  MatNativeDateModule,
  MatPaginatorModule,
  MatProgressBarModule,
  MatProgressSpinnerModule,
  MatRadioModule,
  MatRippleModule,
  MatSelectModule,
  MatSidenavModule,
  MatSliderModule,
  MatSlideToggleModule,
  MatSnackBarModule,
  MatSortModule,
  MatTableModule,
  MatTabsModule,
  MatToolbarModule,
  MatTooltipModule,
  MatStepperModule,
} from '@angular/material';
import { CdkTableModule } from '@angular/cdk/table';
import { RouterModule, Routes } from '@angular/router';
import { NgxPermissionsModule, NgxPermissionsGuard } from 'ngx-permissions';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { environment } from '../../environments/environment';
import { AgmCoreModule } from '@agm/core';
import { AgmDirectionModule } from 'agm-direction';

import { AuthService } from '@app/auth';
import { SystemConfigService } from './system-config.service';
import { User } from '@app/auth';

import { CalloutDetailsComponent } from '../callout-details/callout-details.component';
import { CalloutDetailsService } from '../callout-details/callout-details.service';
import { GoogleApiService } from './google-api.service';
import { DeleteDialogComponent } from './dialogs/delete/delete-dialog.component';
import { DeleteDataService } from './dialogs/data-service';
import { SendMessageComponent } from './views/send-message/send-message.component';
import { MessageContext, SendMessageService } from './send-message.service';

export * from './dialogs/data-service';
const menuRoutes: Routes = [
  //{ path: '', redirectTo: '/login', pathMatch: 'full' },
  { path: 'call-details', component: CalloutDetailsComponent },
];

@NgModule({
  imports: [
    CommonModule,

    NgxPermissionsModule.forChild(),
    RouterModule.forChild(menuRoutes),

    MatPaginatorModule, MatSortModule, MatTableModule, MatProgressSpinnerModule,
    MatNativeDateModule, MatIconModule,
    FormsModule, ReactiveFormsModule,
    AgmCoreModule.forRoot({ apiKey: environment.google_api_key }),
    AgmDirectionModule,
    MatDialogModule,

  ],
  exports: [
    CdkTableModule,
    MatAutocompleteModule,
    MatButtonModule,
    MatButtonToggleModule,
    MatCardModule,
    MatCheckboxModule,
    MatChipsModule,
    MatStepperModule,
    MatDatepickerModule,
    MatDialogModule,
    MatExpansionModule,
    MatGridListModule,
    MatIconModule,
    MatInputModule,
    MatListModule,
    MatMenuModule,
    MatNativeDateModule,
    MatPaginatorModule,
    MatProgressBarModule,
    MatProgressSpinnerModule,
    MatRadioModule,
    MatRippleModule,
    MatSelectModule,
    MatSidenavModule,
    MatSliderModule,
    MatSlideToggleModule,
    MatSnackBarModule,
    MatSortModule,
    MatTableModule,
    MatTabsModule,
    MatToolbarModule,
    MatTooltipModule,

    CalloutDetailsComponent,
    DeleteDialogComponent,
    SendMessageComponent,
  ],
  declarations: [
    CalloutDetailsComponent,
    DeleteDialogComponent,
    SendMessageComponent
  ],
  entryComponents: [
    DeleteDialogComponent,
    SendMessageComponent
  ],
})

export class SystemSharedModule {
  static forRoot(): ModuleWithProviders {
    return {
      ngModule: SystemSharedModule,
      providers: [
                  AuthService,
                  SystemConfigService,
                  CalloutDetailsService,
                  GoogleApiService,
                  SendMessageService
                ]
    };
  }
}
