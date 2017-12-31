import { TestBed, inject } from '@angular/core/testing';

import { SystemConfigService } from './system-config.service';

describe('SystemConfigService', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [SystemConfigService]
    });
  });

  it('should be created', inject([SystemConfigService], (service: SystemConfigService) => {
    expect(service).toBeTruthy();
  }));
});
