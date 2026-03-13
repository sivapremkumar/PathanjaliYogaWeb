import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { LucideAngularModule, LayoutDashboard, Users, Image, MessageSquare, Heart, LogOut, Newspaper } from 'lucide-angular';
import { RouterModule } from '@angular/router';
import { DonationsComponent } from '../donations/donations.component';
import { TrusteesComponent } from '../trustees/trustees.component';
import { NewsComponent } from '../news/news.component';
import { GalleryAdminComponent } from '../gallery/gallery.component';
import { InquiriesComponent } from '../inquiries/inquiries.component';

@Component({
    selector: 'app-dashboard',
    standalone: true,
    imports: [CommonModule, LucideAngularModule, RouterModule, DonationsComponent, TrusteesComponent, NewsComponent, GalleryAdminComponent, InquiriesComponent],
    templateUrl: './dashboard.component.html',
    styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
    stats: any = { totalDonations: 0, donationCount: 0, galleryCount: 0, newInquiries: 0, trusteeCount: 0 };
    activeTab = 'stats';

    readonly LayoutDashboard = LayoutDashboard;
    readonly Users = Users;
    readonly Image = Image;
    readonly Newspaper = Newspaper;
    readonly MessageSquare = MessageSquare;
    readonly Heart = Heart;
    readonly LogOut = LogOut;

    constructor(private api: ApiService, private auth: AuthService) { }

    ngOnInit() {
        this.api.getAdminStats().subscribe(res => {
            this.stats = res;
        });
    }

    logout() {
        this.auth.logout();
    }
}
