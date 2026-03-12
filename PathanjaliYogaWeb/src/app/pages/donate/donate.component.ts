import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';
import { LucideAngularModule, Heart, ShieldCheck, Download } from 'lucide-angular';
import { environment } from '../../../environments/environment';

declare var Razorpay: any;

@Component({
    selector: 'app-donate',
    standalone: true,
    imports: [CommonModule, FormsModule, LucideAngularModule],
    templateUrl: './donate.component.html',
    styleUrls: ['./donate.component.css']
})
export class DonateComponent {
    donation = { donorName: '', amount: 500, email: '', phone: '' };
    isPaymentSuccessful = false;
    receiptId?: number;

    readonly Heart = Heart;
    readonly ShieldCheck = ShieldCheck;
    readonly Download = Download;

    constructor(private api: ApiService) { }

    onDonate() {
        this.api.createDonationOrder(this.donation).subscribe(res => {
            this.api.getRazorpayKey().subscribe(keyRes => {
                this.payWithRazorpay(res.orderId, keyRes.key);
            });
        });
    }

    payWithRazorpay(orderId: string, razorpayKey: string) {
        const options = {
            key: razorpayKey,
            amount: this.donation.amount * 100,
            currency: "INR",
            name: "Sri Padhanjali Yoga Trust",
            description: "Donation for Welfare Programs",
            order_id: orderId,
            handler: (response: any) => {
                this.verifyPayment(response);
            },
            prefill: {
                name: this.donation.donorName,
                email: this.donation.email,
                contact: this.donation.phone
            },
            theme: { color: "#2C5F2D" }
        };

        const rzp = new Razorpay(options);
        rzp.open();
    }

    verifyPayment(payment: any) {
        const payload = {
            orderId: payment.razorpay_order_id,
            paymentId: payment.razorpay_payment_id,
            signature: payment.razorpay_signature
        };

        this.api.verifyPayment(payload).subscribe(res => {
            this.isPaymentSuccessful = true;
            // In real scenario, return receipt ID
        });
    }

    downloadReceipt() {
        // Trigger backend PDF download
        window.open(`${environment.apiUrl}/Donation/receipt/${this.receiptId}`, '_blank');
    }
}
