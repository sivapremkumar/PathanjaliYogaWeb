import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Plus, Trash2, Edit, Upload, Loader } from 'lucide-angular';

@Component({
    selector: 'app-trustees',
    standalone: true,
    imports: [CommonModule, FormsModule, LucideAngularModule],
    templateUrl: './trustees.component.html'
})
export class TrusteesComponent implements OnInit {
    readonly MAX_UPLOAD_MB = 10;
    trustees: any[] = [];
    newTrustee = { name: '', role: '', description: '', imageUrl: '' };
    showForm = false;

    // File upload state
    selectedFile: File | null = null;
    imagePreviewUrl: string | null = null;
    isUploading = false;

    // Edit state
    editingTrustee: any | null = null;
    editFile: File | null = null;
    editPreviewUrl: string | null = null;

    readonly Plus = Plus;
    readonly Trash2 = Trash2;
    readonly Edit = Edit;
    readonly Upload = Upload;
    readonly Loader = Loader;

    constructor(private api: ApiService) { }

    ngOnInit() {
        this.loadTrustees();
    }

    loadTrustees() {
        this.api.getTrustees().subscribe(data => {
            this.trustees = data.map((t: any) => ({
                ...t,
                imageUrl: t.imageUrl || t.image_url || null
            }));
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
            reader.readAsDataURL(this.selectedFile);
            this.newTrustee.imageUrl = '';
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

    addTrustee() {
        if (this.selectedFile) {
            this.isUploading = true;
            this.api.uploadTrusteeImage(this.selectedFile).subscribe({
                next: (res: any) => {
                    this.newTrustee.imageUrl = res.url;
                    this.submitNewTrustee();
                },
                error: (err) => {
                    this.isUploading = false;
                    alert(this.getApiErrorMessage(err, 'Image upload failed. Please try again.'));
                }
            });
        } else {
            this.submitNewTrustee();
        }
    }

    private submitNewTrustee() {
        this.api.createTrustee(this.newTrustee).subscribe(() => {
            this.loadTrustees();
            this.newTrustee = { name: '', role: '', description: '', imageUrl: '' };
            this.selectedFile = null;
            this.imagePreviewUrl = null;
            this.isUploading = false;
            this.showForm = false;
        });
    }

    editTrustee(t: any) {
        this.editingTrustee = { ...t };
        this.editFile = null;
        this.editPreviewUrl = null;
    }

    cancelEdit() {
        this.editingTrustee = null;
        this.editFile = null;
        this.editPreviewUrl = null;
    }

    saveEdit() {
        if (this.editFile) {
            this.isUploading = true;
            this.api.uploadTrusteeImage(this.editFile).subscribe({
                next: (res: any) => {
                    this.editingTrustee.imageUrl = res.url;
                    this.submitEdit();
                },
                error: (err) => {
                    this.isUploading = false;
                    alert(this.getApiErrorMessage(err, 'Image upload failed. Please try again.'));
                }
            });
        } else {
            this.submitEdit();
        }
    }

    private submitEdit() {
        this.api.updateTrustee(this.editingTrustee.id, this.editingTrustee).subscribe(() => {
            this.isUploading = false;
            this.cancelEdit();
            this.loadTrustees();
        });
    }

    deleteTrustee(id: number) {
        if (confirm('Are you certain you want to remove this trustee?')) {
            this.api.deleteTrustee(id).subscribe(() => this.loadTrustees());
        }
    }
}
