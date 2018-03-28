import { Observable } from 'rxjs/Observable';

export interface DeleteDataService {
    delete(data: any): Promise<any>;
}
