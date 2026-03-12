import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { catchError, tap, throwError } from 'rxjs';
import { Router } from '@angular/router';
import { environment } from '../../environments/environment';

@Injectable({
    providedIn: 'root'
})
export class AuthService {
    private apiUrl = `${environment.apiUrl}/auth`;
    private fallbackApiUrl = `${environment.apiUrl}/api/auth`;

    constructor(private http: HttpClient, private router: Router) { }

    login(credentials: any) {
        return this.http.post<any>(`${this.apiUrl}/login`, credentials).pipe(
            catchError(() => this.http.post<any>(`${this.fallbackApiUrl}/login`, credentials)),
            tap(res => {
                if (res.token) {
                    localStorage.setItem('yoga_token', res.token);
                    localStorage.setItem('yoga_user', res.username);
                }
            }),
            catchError(err => throwError(() => err))
        );
    }

    logout() {
        localStorage.removeItem('yoga_token');
        localStorage.removeItem('yoga_user');
        this.router.navigate(['/admin/login']);
    }

    isLoggedIn() {
        return !!localStorage.getItem('yoga_token');
    }

    getUser() {
        return localStorage.getItem('yoga_user');
    }
}
