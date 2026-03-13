import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { LucideAngularModule, LayoutDashboard, Users, Image, MessageSquare, Heart, LogOut, Newspaper, BookOpen } from 'lucide-angular';
import { RouterModule } from '@angular/router';
import { DonationsComponent } from '../donations/donations.component';
import { TrusteesComponent } from '../trustees/trustees.component';
import { NewsComponent } from '../news/news.component';
import { GalleryAdminComponent } from '../gallery/gallery.component';
import { InquiriesComponent } from '../inquiries/inquiries.component';
import { ProgramsAdminComponent } from '../programs/programs.component';

@Component({
    selector: 'app-dashboard',
    standalone: true,
    imports: [CommonModule, LucideAngularModule, RouterModule, DonationsComponent, TrusteesComponent, NewsComponent, GalleryAdminComponent, ProgramsAdminComponent, InquiriesComponent],
    templateUrl: './dashboard.component.html',
    styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
    stats: any = { totalDonations: 0, donationCount: 0, galleryCount: 0, newInquiries: 0, trusteeCount: 0 };
    activeTab = 'stats';
    logoLoadFailed = false;
    readonly adminLogoUrl = 'logo_main.jpeg';
    readonly todayLabel = new Date().toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });

    readonly LayoutDashboard = LayoutDashboard;
    readonly Users = Users;
    readonly Image = Image;
    readonly BookOpen = BookOpen;
    readonly Newspaper = Newspaper;
    readonly MessageSquare = MessageSquare;
    readonly Heart = Heart;
    readonly LogOut = LogOut;

    readonly navItems = [
        { key: 'stats', label: 'Overview', icon: LayoutDashboard },
        { key: 'donations', label: 'Donations', icon: Heart },
        { key: 'trustees', label: 'Board of Trustees', icon: Users },
        { key: 'news', label: 'News Management', icon: Newspaper },
        { key: 'gallery', label: 'Gallery Management', icon: Image },
        { key: 'programs', label: 'Programs Management', icon: BookOpen },
        { key: 'inquiries', label: 'Inquiries', icon: MessageSquare },
    ];

    constructor(private api: ApiService, private auth: AuthService) { }

    ngOnInit() {
        this.api.getAdminStats().subscribe(res => {
            this.stats = res;
        });
    }

    get activeTabTitle(): string {
        const current = this.navItems.find(item => item.key === this.activeTab);
        return current ? current.label : 'Dashboard';
    }

    logout() {
        this.auth.logout();
    }
}
