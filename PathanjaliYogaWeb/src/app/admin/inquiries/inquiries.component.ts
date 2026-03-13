import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { LucideAngularModule, MessageSquare, CheckCircle, Clock } from 'lucide-angular';

@Component({
    selector: 'app-inquiries',
    standalone: true,
    imports: [CommonModule, LucideAngularModule],
    templateUrl: './inquiries.component.html'
})
export class InquiriesComponent implements OnInit {
    inquiries: any[] = [];
    readonly MessageSquare = MessageSquare;
    readonly CheckCircle = CheckCircle;
    readonly Clock = Clock;

    constructor(private api: ApiService) { }

    private normalizeInquiry(item: any) {
        return {
            id: item.id,
            name: (item.name ?? '').trim(),
            email: (item.email ?? '').trim(),
            message: (item.message ?? '').trim(),
            createdAt: item.createdAt ?? item.created_at ?? null,
        };
    }

    ngOnInit() {
        this.api.getInquiries().subscribe(data => {
            this.inquiries = data
                .map(item => this.normalizeInquiry(item))
                .filter(item => item.name !== '' || item.email !== '' || item.message !== '');
        });
    }
}
