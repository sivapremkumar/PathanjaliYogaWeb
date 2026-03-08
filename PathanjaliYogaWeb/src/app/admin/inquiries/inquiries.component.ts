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

    ngOnInit() {
        this.api.getInquiries().subscribe(data => {
            this.inquiries = data;
        });
    }
}
