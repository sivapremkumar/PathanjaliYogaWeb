import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Plus, Image, Trash2 } from 'lucide-angular';

@Component({
    selector: 'app-news',
    standalone: true,
    imports: [CommonModule, FormsModule, LucideAngularModule],
    templateUrl: './news.component.html'
})
export class NewsComponent implements OnInit {
    newsItems: any[] = [];
    newItem = { title: '', description: '', imageUrl: '' };
    showForm = false;

    readonly Plus = Plus;
    readonly Image = Image;
    readonly Trash2 = Trash2;

    constructor(private api: ApiService) { }

    ngOnInit() {
        this.loadNews();
    }

    private normalizeItem(item: any) {
        return {
            id: item.id,
            title: item.title ?? '',
            description: item.description ?? item.content ?? '',
            imageUrl: item.imageUrl ?? item.image_url ?? ((typeof item.location === 'string' && item.location.startsWith('http')) ? item.location : ''),
            createdAt: item.createdAt ?? item.created_at ?? item.date ?? null,
        };
    }

    loadNews() {
        this.api.getNews().subscribe(data => this.newsItems = data.map(item => this.normalizeItem(item)));
    }

    addNews() {
        const payload = {
            title: this.newItem.title,
            description: this.newItem.description,
            content: this.newItem.description,
            imageUrl: this.newItem.imageUrl,
            location: this.newItem.imageUrl,
            is_event: false,
            date: new Date().toISOString().slice(0, 10),
        };

        this.api.createNews(payload).subscribe(() => {
            this.loadNews();
            this.newItem = { title: '', description: '', imageUrl: '' };
            this.showForm = false;
        });
    }

    deleteNews(id: number) {
        if (!confirm('Delete this post?')) {
            return;
        }

        this.api.deleteNews(id).subscribe(() => {
            this.newsItems = this.newsItems.filter(item => item.id !== id);
        });
    }
}
