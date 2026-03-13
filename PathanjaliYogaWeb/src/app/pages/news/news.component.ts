import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { LucideAngularModule, Calendar, MapPin, ArrowRight, ChevronLeft, ChevronRight } from 'lucide-angular';
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
    currentPage = 1;
    readonly pageSize = 4;
    isPageTransitioning = false;
    readonly Calendar = Calendar;
    readonly MapPin = MapPin;
    readonly ArrowRight = ArrowRight;
    readonly ChevronLeft = ChevronLeft;
    readonly ChevronRight = ChevronRight;

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

            this.currentPage = 1;
        });
    }

    get totalPages(): number {
        return Math.max(1, Math.ceil(this.newsItems.length / this.pageSize));
    }

    get paginatedNewsItems(): any[] {
        const start = (this.currentPage - 1) * this.pageSize;
        return this.newsItems.slice(start, start + this.pageSize);
    }

    goToPage(page: number) {
        const targetPage = Math.min(Math.max(1, page), this.totalPages);
        if (targetPage === this.currentPage || this.isPageTransitioning) {
            return;
        }

        this.isPageTransitioning = true;
        setTimeout(() => {
            this.currentPage = targetPage;
            this.isPageTransitioning = false;
        }, 180);
    }

    nextPage() {
        this.goToPage(this.currentPage + 1);
    }

    prevPage() {
        this.goToPage(this.currentPage - 1);
    }
}
