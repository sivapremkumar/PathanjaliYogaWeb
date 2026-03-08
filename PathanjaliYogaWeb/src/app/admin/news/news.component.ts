import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Plus, Image } from 'lucide-angular';

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

    constructor(private api: ApiService) { }

    ngOnInit() {
        this.loadNews();
    }

    loadNews() {
        this.api.getNews().subscribe(data => this.newsItems = data);
    }

    addNews() {
        this.api.createNews(this.newItem).subscribe(() => {
            this.loadNews();
            this.newItem = { title: '', description: '', imageUrl: '' };
            this.showForm = false;
        });
    }
}
