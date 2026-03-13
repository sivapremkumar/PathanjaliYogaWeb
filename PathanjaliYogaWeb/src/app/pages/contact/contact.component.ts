import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';
import { LucideAngularModule, Phone, Mail, MapPin, Send, CheckCircle } from 'lucide-angular';

@Component({
    selector: 'app-contact',
    standalone: true,
    imports: [CommonModule, FormsModule, LucideAngularModule],
    templateUrl: './contact.component.html',
    styleUrls: ['./contact.component.css']
})
export class ContactComponent {
    inquiry = { name: '', email: '', subject: '', message: '' };
    isSubmitted = false;

    readonly Phone = Phone;
    readonly Mail = Mail;
    readonly MapPin = MapPin;
    readonly Send = Send;
    readonly CheckCircle = CheckCircle;

    constructor(private api: ApiService) { }

    onSubmit() {
        const subject = this.inquiry.subject.trim();
        const body = this.inquiry.message.trim();
        const payload = {
            name: this.inquiry.name,
            email: this.inquiry.email,
            message: subject ? `Subject: ${subject}\n\n${body}` : body,
        };

        this.api.submitInquiry(payload).subscribe(() => {
            this.isSubmitted = true;
            this.inquiry = { name: '', email: '', subject: '', message: '' };
        });
    }
}
