import { TestBed, inject } from '@angular/core/testing';

import { MenuCalloutMonitorService } from './menu-callout-monitor.service';

describe('MenuCalloutMonitorService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [MenuCalloutMonitorService]
    });
  });

  it('should be created', inject([MenuCalloutMonitorService], (service: MenuCalloutMonitorService) => {
    expect(service).toBeTruthy();
  }));
});
