import { TestBed, inject } from '@angular/core/testing';

import { MenuCalloutHistoryService } from './menu-callout-history.service';

describe('MenuCalloutHistoryService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [MenuCalloutHistoryService]
    });
  });

  it('should be created', inject([MenuCalloutHistoryService], (service: MenuCalloutHistoryService) => {
    expect(service).toBeTruthy();
  }));
});
