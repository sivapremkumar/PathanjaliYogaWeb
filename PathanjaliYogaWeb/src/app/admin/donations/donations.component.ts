import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { LucideAngularModule, Download, Search } from 'lucide-angular';

@Component({
    selector: 'app-donations',
    standalone: true,
    imports: [CommonModule, LucideAngularModule],
    templateUrl: './donations.component.html'
})
export class DonationsComponent implements OnInit {
    donations: any[] = [];
    readonly Download = Download;
    readonly Search = Search;

    constructor(private api: ApiService) { }

    private normalizeDonation(item: any) {
        return {
            donorName: item.donor_name ?? item.donorName ?? 'Anonymous',
            donorEmail: item.email ?? item.donorEmail ?? '-',
            amount: item.amount ?? 0,
            createdAt: item.created_at ?? item.createdAt ?? null,
            paymentStatus: item.payment_status ?? item.paymentStatus ?? 'Pending',
            transactionId: item.transaction_id ?? item.transactionId ?? item.razorpayPaymentId ?? item.razorpayOrderId ?? '',
        };
    }

    ngOnInit() {
        this.api.getDonations().subscribe(data => {
            this.donations = data.map(item => this.normalizeDonation(item));
        });
    }
}
