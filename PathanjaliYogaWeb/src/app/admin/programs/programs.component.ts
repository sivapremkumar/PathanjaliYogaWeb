import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Plus, Image, Trash2, Edit, Upload, Loader } from 'lucide-angular';
import { ApiService } from '../../services/api.service';

@Component({
    selector: 'app-programs-admin',
    standalone: true,
    imports: [CommonModule, FormsModule, LucideAngularModule],
    templateUrl: './programs.component.html'
})
export class ProgramsAdminComponent implements OnInit {
    readonly MAX_UPLOAD_MB = 10;
    items: any[] = [];
    newItem = { title: '', description: '', type: 'Program', schedule: '', imageUrl: '' };
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
        this.loadPrograms();
    }

    private normalizeItem(item: any) {
        const candidate = item.imageUrl ?? item.image_url ?? '';
        const imageUrl = typeof candidate === 'string' && (candidate.startsWith('http') || candidate.startsWith('/api/uploads/programs/'))
            ? candidate
            : '';

        return {
            id: item.id,
            title: item.title ?? '',
            description: item.description ?? '',
            type: item.type ?? 'Program',
            schedule: item.schedule ?? '',
            imageUrl,
            createdAt: item.createdAt ?? item.created_at ?? null,
        };
    }

    loadPrograms() {
        this.api.getPrograms().subscribe(data => {
            this.items = data.map(item => this.normalizeItem(item));
        });
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
            reader.readAsDataURL(file);
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
            reader.readAsDataURL(file);
        }
    }

    private getApiErrorMessage(err: any, fallback: string): string {
        return err?.error?.error || err?.error?.message || err?.message || fallback;
    }

    addItem() {
        if (this.selectedFile) {
            this.isUploading = true;
            this.api.uploadProgramImage(this.selectedFile).subscribe({
                next: (res: any) => {
                    this.newItem.imageUrl = res.url;
                    this.submitNewItem();
                },
                error: (err) => {
                    this.isUploading = false;
                    alert(this.getApiErrorMessage(err, 'Image upload failed. Please try again.'));
                }
            });
            return;
        }

        this.submitNewItem();
    }

    private submitNewItem() {
        this.api.createProgram({
            title: this.newItem.title,
            description: this.newItem.description,
            type: this.newItem.type,
            schedule: this.newItem.schedule,
            imageUrl: this.newItem.imageUrl,
        }).subscribe(() => {
            this.loadPrograms();
            this.newItem = { title: '', description: '', type: 'Program', schedule: '', imageUrl: '' };
            this.selectedFile = null;
            this.imagePreviewUrl = null;
            this.isUploading = false;
            this.showForm = false;
        });
    }

    editItem(item: any) {
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
            this.api.uploadProgramImage(this.editFile).subscribe({
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

        this.api.updateProgram(this.editingItem.id, {
            title: this.editingItem.title,
            description: this.editingItem.description,
            type: this.editingItem.type,
            schedule: this.editingItem.schedule,
            imageUrl: this.editingItem.imageUrl,
        }).subscribe(() => {
            this.isUploading = false;
            this.cancelEdit();
            this.loadPrograms();
        });
    }

    deleteItem(item: any) {
        if (!confirm('Delete this program?')) {
            return;
        }

        this.api.deleteProgram(item.id).subscribe(() => {
            this.items = this.items.filter(existing => existing.id !== item.id);
        });
    }
}