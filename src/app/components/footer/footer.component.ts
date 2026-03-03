import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LucideAngularModule, Heart, Facebook, Instagram, Youtube, Phone, Mail, MapPin } from 'lucide-angular';
import { RouterModule } from '@angular/router';

@Component({
    selector: 'app-footer',
    standalone: true,
    imports: [CommonModule, LucideAngularModule, RouterModule],
    templateUrl: './footer.component.html',
    styleUrls: ['./footer.component.css']
})
export class FooterComponent {
    readonly Heart = Heart;
    readonly Facebook = Facebook;
    readonly Instagram = Instagram;
    readonly Youtube = Youtube;
    readonly Phone = Phone;
    readonly Mail = Mail;
    readonly MapPin = MapPin;
}
