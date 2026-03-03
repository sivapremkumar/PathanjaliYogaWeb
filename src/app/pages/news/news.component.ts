import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { LucideAngularModule, Calendar, MapPin, ArrowRight } from 'lucide-angular';
import { RouterModule } from '@angular/router';

@Component({
    selector: 'app-news',
    standalone: true,
    imports: [CommonModule, LucideAngularModule, RouterModule],
    templateUrl: './news.component.html',
    styleUrls: ['./news.component.css']
})
export class NewsComponent implements OnInit {
    newsItems: any[] = [];
    readonly Calendar = Calendar;
    readonly MapPin = MapPin;
    readonly ArrowRight = ArrowRight;

    constructor(private api: ApiService) { }

    ngOnInit() {
        this.api.getNews().subscribe(res => {
            this.newsItems = res;
        });

        // Mock data if empty
        if (this.newsItems.length === 0) {
            this.newsItems = [
                { id: 1, title: 'Yoga Workshop for Seniors', content: 'A weekend workshop for elderly community member to learn gentle yoga.', date: new Date(), location: 'Sankarankoil Center', isEvent: true },
                { id: 2, title: 'Donation Drive Success', content: 'Huge thanks to everyone who participated in our recent food drive.', date: new Date(), location: 'Main Office', isEvent: false }
            ];
        }
    }
}
