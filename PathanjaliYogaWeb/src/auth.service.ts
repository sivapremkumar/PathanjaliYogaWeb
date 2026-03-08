import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs';
import { Router } from '@angular/router';

@Injectable({
    providedIn: 'root'
})
export class AuthService {
    private apiUrl = 'https://localhost:7082/api/Auth';

    constructor(private http: HttpClient, private router: Router) { }

    login(credentials: any) {
        return this.http.post<any>(`${this.apiUrl}/login`, credentials).pipe(
            tap(res => {
                if (res.token) {
                    localStorage.setItem('yoga_token', res.token);
                    localStorage.setItem('yoga_user', res.username);
                }
            })
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
