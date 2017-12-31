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
import { AgmCoreModule, LAZY_MAPS_API_CONFIG , LazyMapsAPILoaderConfigLiteral } from '@agm/core';
import { AgmDirectionModule } from 'agm-direction';

import { AuthService } from '@app/auth';
import { SystemConfigService } from './system-config.service';
import { User } from '@app/auth';

import { CalloutDetailsComponent } from '../callout-details/callout-details.component';
import { CalloutDetailsService } from '../callout-details/callout-details.service';
import { GoogleApiService } from './google-api.service';

const menuRoutes: Routes = [
  //{ path: '', redirectTo: '/login', pathMatch: 'full' },
  { path: 'call-details', component: CalloutDetailsComponent },
];

@Injectable()
export class GoogleMapsConfig implements LazyMapsAPILoaderConfigLiteral {
  public apiKey: string;

  constructor(configService: SystemConfigService) {
    // debugger;
    // console.log('INSIDE MAPs'); //This is not displayed in console
    configService.getSystemConfig().subscribe(config => {
      // debugger;
      // console.log('Google config: ' + config);
      this.apiKey = config.google.maps_api_key;
      // console.log('Google maps API key: ' + this.apiKey);
    });
  }
}

@NgModule({
  imports: [
    CommonModule,

    NgxPermissionsModule.forChild(),
    RouterModule.forChild(menuRoutes),

    MatPaginatorModule, MatSortModule, MatTableModule, MatProgressSpinnerModule,
    MatNativeDateModule, MatIconModule,
    FormsModule, ReactiveFormsModule,
    AgmCoreModule.forRoot(),
    AgmDirectionModule,
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
    AgmCoreModule
  ],
  declarations: [
    CalloutDetailsComponent
  ]
})

export class SystemSharedModule {
  static forRoot(): ModuleWithProviders {
    return {
      ngModule: SystemSharedModule,
      providers: [AuthService,
                  SystemConfigService,
                  CalloutDetailsService,
                  GoogleApiService,
                  { provide: LAZY_MAPS_API_CONFIG, useClass: GoogleMapsConfig }
                ]
    };
  }
}
