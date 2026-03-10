import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { LucideAngularModule, Heart, History, Users, Target } from 'lucide-angular';

@Component({
    selector: 'app-about',
    standalone: true,
    imports: [CommonModule, LucideAngularModule],
    templateUrl: './about.component.html',
    styleUrls: ['./about.component.css']
})
export class AboutComponent implements OnInit {
    trustees: any[] = [];
    readonly History = History;
    readonly Users = Users;
    readonly Target = Target;
    readonly Heart = Heart;

    constructor(private api: ApiService) { }

    ngOnInit() {
        this.api.getTrustees().subscribe(
            res => {
                if (res && res.length > 0) {
                    this.trustees = res;
                } else {
                    this.loadDefaultTrustees();
                }
            },
            err => {
                this.loadDefaultTrustees();
            }
        );

        // Load defaults immediately in case API takes time or fails
        if (this.trustees.length === 0) {
            this.loadDefaultTrustees();
        }
    }

    loadDefaultTrustees() {
        this.trustees = [
            { id: 1, name: 'Jeyaram', role: 'President', bio: '', profileImageUrl: 'jeyaram.jpeg' },
            { id: 2, name: 'Kasimani', role: 'Trustee', bio: '', profileImageUrl: 'kasimani.jpeg' },
            { id: 3, name: 'Esakki', role: 'Trustee', bio: '', profileImageUrl: 'Esakki-Durai_01.jpeg' },
            { id: 4, name: 'Venkatraman', role: 'Trustee', bio: '', profileImageUrl: 'Venkatraman.jpeg' },
            { id: 5, name: 'Marimuthu', role: 'Trustee', bio: '', profileImageUrl: 'marimuthu.jpeg' },
            { id: 6, name: 'Murugan', role: 'Trustee', bio: '', profileImageUrl: 'Murugan.jpeg' },
            { id: 7, name: 'Murugesen', role: 'Trustee', bio: '', profileImageUrl: 'Murugesen.jpeg' }
        ];
    }
}
