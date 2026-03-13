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

    private normalizeItem(item: any) {
        const candidate = item.imageUrl ?? item.image_url ?? item.location ?? '';
        const imageUrl = typeof candidate === 'string' && (candidate.startsWith('http') || candidate.startsWith('/api/uploads/news_event_clips/'))
            ? candidate
            : '';

        return {
            id: item.id,
            title: item.title ?? '',
            content: item.content ?? item.description ?? '',
            date: item.date ?? item.created_at ?? new Date(),
            location: imageUrl ? null : (item.location ?? null),
            imageUrl,
            isEvent: item.isEvent ?? item.is_event ?? false,
        };
    }

    ngOnInit() {
        this.api.getNews().subscribe(res => {
            this.newsItems = res.map(item => this.normalizeItem(item));

            if (this.newsItems.length === 0) {
                this.newsItems = [
                    { id: 1, title: 'Yoga Workshop for Seniors', content: 'A weekend workshop for elderly community member to learn gentle yoga.', date: new Date(), location: 'Sankarankoil Center', imageUrl: '', isEvent: true },
                    { id: 2, title: 'Donation Drive Success', content: 'Huge thanks to everyone who participated in our recent food drive.', date: new Date(), location: 'Main Office', imageUrl: '', isEvent: false }
                ];
            }
        });
    }
}
