import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';
import { LucideAngularModule, Heart, ShieldCheck, Download } from 'lucide-angular';

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
    isPaymentCompleted = false;
    isSubmitting = false;
    statusMessage = '';
    receiptId?: number;

    readonly Heart = Heart;
    readonly ShieldCheck = ShieldCheck;
    readonly Download = Download;

    constructor(private api: ApiService) { }

    onDonate() {
        if (this.isSubmitting) {
            return;
        }

        this.isSubmitting = true;
        this.statusMessage = '';

        const payload = {
            donor_name: this.donation.donorName,
            email: this.donation.email,
            phone: this.donation.phone,
            amount: this.donation.amount,
        };

        this.api.createDonationOrder(payload).subscribe({
            next: (res) => {
                const orderId = String(res?.orderId ?? res?.order_id ?? res?.id ?? '');
                if (!orderId) {
                    this.isSubmitting = false;
                    this.statusMessage = 'Unable to create donation request. Please try again.';
                    return;
                }

                this.receiptId = Number(orderId);

                this.api.getRazorpayKey().subscribe({
                    next: (keyRes: any) => {
                        const razorpayKey = String(keyRes?.key ?? '').trim();
                        const isRazorpayReady = razorpayKey !== '' && typeof Razorpay !== 'undefined';

                        if (!isRazorpayReady) {
                            this.isPaymentSuccessful = true;
                            this.isPaymentCompleted = false;
                            this.isSubmitting = false;
                            this.statusMessage = 'Donation request saved successfully. Payment gateway is not configured yet.';
                            return;
                        }

                        this.payWithRazorpay(orderId, razorpayKey);
                    },
                    error: () => {
                        this.isPaymentSuccessful = true;
                        this.isPaymentCompleted = false;
                        this.isSubmitting = false;
                        this.statusMessage = 'Donation request saved successfully. Payment gateway key could not be loaded.';
                    }
                });
            },
            error: (err) => {
                this.isSubmitting = false;
                this.statusMessage = err?.error?.error || err?.error?.message || 'Unable to submit donation request. Please try again.';
            }
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
                this.verifyPayment({
                    ...response,
                    id: Number(orderId),
                    orderId,
                });
            },
            modal: {
                ondismiss: () => {
                    this.isSubmitting = false;
                }
            },
            prefill: {
                name: this.donation.donorName,
                email: this.donation.email,
                contact: this.donation.phone
            },
            theme: { color: "#2C5F2D" }
        };

        try {
            const rzp = new Razorpay(options);
            rzp.open();
        } catch {
            this.isSubmitting = false;
            this.statusMessage = 'Payment gateway is currently unavailable. Donation request has been saved.';
            this.isPaymentSuccessful = true;
            this.isPaymentCompleted = false;
        }
    }

    verifyPayment(payment: any) {
        const payload = {
            id: payment.id ?? payment.orderId ?? payment.razorpay_order_id,
            orderId: payment.orderId ?? payment.razorpay_order_id,
            paymentId: payment.razorpay_payment_id,
            signature: payment.razorpay_signature,
            transaction_id: payment.razorpay_payment_id,
        };

        this.api.verifyPayment(payload).subscribe({
            next: (res) => {
                this.isPaymentSuccessful = true;
                this.isPaymentCompleted = true;
                this.isSubmitting = false;
                this.statusMessage = 'Payment successful. Thank you for your generous contribution.';
                const donationId = Number(res?.donation?.id ?? payload.id ?? 0);
                if (donationId > 0) {
                    this.receiptId = donationId;
                }
            },
            error: (err) => {
                this.isSubmitting = false;
                this.statusMessage = err?.error?.error || err?.error?.message || 'Payment verification failed. Please contact support.';
            }
        });
    }

    downloadReceipt() {
        alert('Receipt download will be enabled after payment gateway setup is completed.');
    }
}
