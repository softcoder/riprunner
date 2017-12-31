import { TestBed, inject } from '@angular/core/testing';

import { CalloutDetailsService } from './callout-details.service';

describe('CalloutDetailsService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [CalloutDetailsService]
    });
  });

  it('should be created', inject([CalloutDetailsService], (service: CalloutDetailsService) => {
    expect(service).toBeTruthy();
  }));
});
