import { TestBed, inject } from '@angular/core/testing';

import { LogoffService } from './logoff.service';

describe('LogoffService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [LogoffService]
    });
  });

  it('should be created', inject([LogoffService], (service: LogoffService) => {
    expect(service).toBeTruthy();
  }));
});
