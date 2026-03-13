import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({
    providedIn: 'root'
})
export class ApiService {
    private apiUrl = environment.apiUrl;

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
        return this.http.get<any[]>(`${this.apiUrl}/trustees`);
    }

    createTrustee(trustee: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/trustees`, trustee, { headers: this.getHeaders() });
    }

    deleteTrustee(id: number): Observable<any> {
        return this.http.delete(`${this.apiUrl}/trustees/${id}`, { headers: this.getHeaders() });
    }

    updateTrustee(id: number, data: any): Observable<any> {
        return this.http.put(`${this.apiUrl}/trustees/${id}`, data, { headers: this.getHeaders() });
    }

    uploadTrusteeImage(file: File): Observable<any> {
        const token = localStorage.getItem('yoga_token');
        const formData = new FormData();
        formData.append('image', file);
        return this.http.post(`${this.apiUrl}/trustees/upload`, formData, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
    }

    // Donations
    createDonationOrder(donation: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/donations/order`, donation);
    }

    verifyPayment(payment: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/donations/verify`, payment);
    }

    getDonations(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/donations`, { headers: this.getHeaders() });
    }

    // Razorpay Key
    getRazorpayKey(): Observable<any> {
        return this.http.get(`${this.apiUrl}/donations/razorpay-key`);
    }

    // News
    getNews(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/news`);
    }

    uploadNewsImage(file: File): Observable<any> {
        const token = localStorage.getItem('yoga_token');
        const formData = new FormData();
        formData.append('image', file);
        return this.http.post(`${this.apiUrl}/news/upload`, formData, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
    }

    createNews(item: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/news`, item, { headers: this.getHeaders() });
    }

    updateNews(id: number, item: any): Observable<any> {
        return this.http.put(`${this.apiUrl}/news/${id}`, item, { headers: this.getHeaders() });
    }

    deleteNews(id: number): Observable<any> {
        return this.http.delete(`${this.apiUrl}/news/${id}`, { headers: this.getHeaders() });
    }

    // Gallery
    getGallery(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/gallery`);
    }

    uploadGalleryImage(file: File): Observable<any> {
        const token = localStorage.getItem('yoga_token');
        const formData = new FormData();
        formData.append('image', file);
        return this.http.post(`${this.apiUrl}/gallery/upload`, formData, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
    }

    createGallery(item: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/gallery`, item, { headers: this.getHeaders() });
    }

    updateGallery(id: number, item: any): Observable<any> {
        return this.http.put(`${this.apiUrl}/gallery/${id}`, item, { headers: this.getHeaders() });
    }

    deleteGallery(id: number): Observable<any> {
        return this.http.delete(`${this.apiUrl}/gallery/${id}`, { headers: this.getHeaders() });
    }

    // Programs
    getPrograms(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/programs`);
    }

    uploadProgramImage(file: File): Observable<any> {
        const token = localStorage.getItem('yoga_token');
        const formData = new FormData();
        formData.append('image', file);
        return this.http.post(`${this.apiUrl}/programs/upload`, formData, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
    }

    createProgram(item: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/programs`, item, { headers: this.getHeaders() });
    }

    updateProgram(id: number, item: any): Observable<any> {
        return this.http.put(`${this.apiUrl}/programs/${id}`, item, { headers: this.getHeaders() });
    }

    deleteProgram(id: number): Observable<any> {
        return this.http.delete(`${this.apiUrl}/programs/${id}`, { headers: this.getHeaders() });
    }

    // Inquiries
    submitInquiry(inquiry: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/inquiries`, inquiry);
    }

    getInquiries(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/inquiries`, { headers: this.getHeaders() });
    }

    resolveInquiry(id: number): Observable<any> {
        return this.http.patch(`${this.apiUrl}/inquiries/${id}/resolve`, {}, { headers: this.getHeaders() });
    }

    // Admin Stats
    getAdminStats(): Observable<any> {
        return this.http.get(`${this.apiUrl}/admin/stats`, { headers: this.getHeaders() });
    }

    // Admin Security
    changeAdminPassword(payload: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/auth/change-password`, payload, { headers: this.getHeaders() });
    }
}
