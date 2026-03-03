import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { LucideAngularModule, LayoutDashboard, Users, Image, MessageSquare, Heart, LogOut } from 'lucide-angular';
import { RouterModule } from '@angular/router';

@Component({
    selector: 'app-dashboard',
    standalone: true,
    imports: [CommonModule, LucideAngularModule, RouterModule],
    templateUrl: './dashboard.component.html',
    styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
    stats: any = { totalDonations: 0, donationCount: 0, galleryCount: 0, newInquiries: 0, trusteeCount: 0 };
    activeTab = 'stats';

    readonly LayoutDashboard = LayoutDashboard;
    readonly Users = Users;
    readonly Image = Image;
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
