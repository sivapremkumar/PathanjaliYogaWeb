import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Plus, Image, Trash2, Edit, Upload, Loader } from 'lucide-angular';

@Component({
    selector: 'app-news',
    standalone: true,
    imports: [CommonModule, FormsModule, LucideAngularModule],
    templateUrl: './news.component.html'
})
export class NewsComponent implements OnInit {
    readonly MAX_UPLOAD_MB = 10;
    newsItems: any[] = [];
    newItem = { title: '', description: '', imageUrl: '', is_event: false };
    showForm = false;

    selectedFile: File | null = null;
    imagePreviewUrl: string | null = null;
    isUploading = false;

    editingItem: any | null = null;
    editFile: File | null = null;
    editPreviewUrl: string | null = null;

    readonly Plus = Plus;
    readonly Image = Image;
    readonly Trash2 = Trash2;
    readonly Edit = Edit;
    readonly Upload = Upload;
    readonly Loader = Loader;

    constructor(private api: ApiService) { }

    ngOnInit() {
        this.loadNews();
    }

    private normalizeItem(item: any) {
        const candidate = item.imageUrl ?? item.image_url ?? item.location ?? '';
        const imageUrl = typeof candidate === 'string' && (candidate.startsWith('http') || candidate.startsWith('/api/uploads/news_event_clips/'))
            ? candidate
            : '';

        return {
            id: item.id,
            title: item.title ?? '',
            description: item.description ?? item.content ?? '',
            imageUrl,
            is_event: !!(item.is_event ?? item.isEvent ?? false),
            createdAt: item.createdAt ?? item.created_at ?? item.date ?? null,
        };
    }

    loadNews() {
        this.api.getNews().subscribe(data => this.newsItems = data.map(item => this.normalizeItem(item)));
    }

    onFileSelected(event: Event) {
        const input = event.target as HTMLInputElement;
        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file.');
                input.value = '';
                return;
            }
            if (file.size > this.MAX_UPLOAD_MB * 1024 * 1024) {
                alert(`Image is too large. Max allowed is ${this.MAX_UPLOAD_MB} MB.`);
                input.value = '';
                return;
            }
            this.selectedFile = file;
            const reader = new FileReader();
            reader.onload = () => this.imagePreviewUrl = reader.result as string;
            reader.readAsDataURL(this.selectedFile);
            this.newItem.imageUrl = '';
        }
    }

    onEditFileSelected(event: Event) {
        const input = event.target as HTMLInputElement;
        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file.');
                input.value = '';
                return;
            }
            if (file.size > this.MAX_UPLOAD_MB * 1024 * 1024) {
                alert(`Image is too large. Max allowed is ${this.MAX_UPLOAD_MB} MB.`);
                input.value = '';
                return;
            }
            this.editFile = file;
            const reader = new FileReader();
            reader.onload = () => this.editPreviewUrl = reader.result as string;
            reader.readAsDataURL(this.editFile);
        }
    }

    private getApiErrorMessage(err: any, fallback: string): string {
        return err?.error?.error || err?.error?.message || err?.message || fallback;
    }

    addNews() {
        if (this.selectedFile) {
            this.isUploading = true;
            this.api.uploadNewsImage(this.selectedFile).subscribe({
                next: (res: any) => {
                    this.newItem.imageUrl = res.url;
                    this.submitNewNews();
                },
                error: (err) => {
                    this.isUploading = false;
                    alert(this.getApiErrorMessage(err, 'Image upload failed. Please try again.'));
                }
            });
        } else {
            this.submitNewNews();
        }
    }

    private submitNewNews() {
        const payload = {
            title: this.newItem.title,
            description: this.newItem.description,
            content: this.newItem.description,
            imageUrl: this.newItem.imageUrl,
            location: this.newItem.imageUrl,
            is_event: this.newItem.is_event,
            date: new Date().toISOString().slice(0, 10),
        };

        this.api.createNews(payload).subscribe(() => {
            this.loadNews();
            this.newItem = { title: '', description: '', imageUrl: '', is_event: false };
            this.selectedFile = null;
            this.imagePreviewUrl = null;
            this.isUploading = false;
            this.showForm = false;
        });
    }

    editNews(item: any) {
        this.editingItem = { ...item };
        this.editFile = null;
        this.editPreviewUrl = null;
        this.showForm = false;
    }

    cancelEdit() {
        this.editingItem = null;
        this.editFile = null;
        this.editPreviewUrl = null;
    }

    saveEdit() {
        if (!this.editingItem) {
            return;
        }

        if (this.editFile) {
            this.isUploading = true;
            this.api.uploadNewsImage(this.editFile).subscribe({
                next: (res: any) => {
                    this.editingItem.imageUrl = res.url;
                    this.submitEdit();
                },
                error: (err) => {
                    this.isUploading = false;
                    alert(this.getApiErrorMessage(err, 'Image upload failed. Please try again.'));
                }
            });
            return;
        }

        this.submitEdit();
    }

    private submitEdit() {
        if (!this.editingItem) {
            return;
        }
        const payload = {
            title: this.editingItem.title,
            description: this.editingItem.description,
            content: this.editingItem.description,
            imageUrl: this.editingItem.imageUrl,
            location: this.editingItem.imageUrl,
            is_event: this.editingItem.is_event,
        };
        this.api.updateNews(this.editingItem.id, payload).subscribe(() => {
            this.isUploading = false;
            this.cancelEdit();
            this.loadNews();
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
