import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({
    providedIn: 'root'
})
export class ApiService {
    private apiUrl = 'https://localhost:7082/api'; // Update port if different

    constructor(private http: HttpClient) { }

    private getHeaders() {
        const token = localStorage.getItem('yoga_token');
        return new HttpHeaders({
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        });
    }

    // Trustees
    getTrustees(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/Trustee`);
    }

    createTrustee(trustee: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/Trustee`, trustee, { headers: this.getHeaders() });
    }

    deleteTrustee(id: number): Observable<any> {
        return this.http.delete(`${this.apiUrl}/Trustee/${id}`, { headers: this.getHeaders() });
    }

    // Donations
    createDonationOrder(donation: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/Donation/order`, donation);
    }

    verifyPayment(payment: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/Donation/verify`, payment);
    }

    getDonations(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/Donation`, { headers: this.getHeaders() });
    }

    // News
    getNews(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/NewsEvent`);
    }

    createNews(item: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/NewsEvent`, item, { headers: this.getHeaders() });
    }

    // Inquiries
    submitInquiry(inquiry: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/Inquiry`, inquiry);
    }

    getInquiries(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/Inquiry`, { headers: this.getHeaders() });
    }

    // Admin Stats
    getAdminStats(): Observable<any> {
        return this.http.get(`${this.apiUrl}/AdminDashboard/stats`, { headers: this.getHeaders() });
    }
}
