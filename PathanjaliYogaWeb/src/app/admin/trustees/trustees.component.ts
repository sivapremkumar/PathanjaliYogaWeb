import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule, Plus, Trash2, Edit, Upload, Loader, Database } from 'lucide-angular';

@Component({
    selector: 'app-trustees',
    standalone: true,
    imports: [CommonModule, FormsModule, LucideAngularModule],
    templateUrl: './trustees.component.html'
})
export class TrusteesComponent implements OnInit {
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

    // Seed state
    isSeeding = false;
    seedMessage = '';

    readonly Plus = Plus;
    readonly Trash2 = Trash2;
    readonly Edit = Edit;
    readonly Upload = Upload;
    readonly Loader = Loader;
    readonly Database = Database;

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
            this.selectedFile = input.files[0];
            const reader = new FileReader();
            reader.onload = () => this.imagePreviewUrl = reader.result as string;
            reader.readAsDataURL(this.selectedFile);
            this.newTrustee.imageUrl = '';
        }
    }

    onEditFileSelected(event: Event) {
        const input = event.target as HTMLInputElement;
        if (input.files && input.files[0]) {
            this.editFile = input.files[0];
            const reader = new FileReader();
            reader.onload = () => this.editPreviewUrl = reader.result as string;
            reader.readAsDataURL(this.editFile);
        }
    }

    addTrustee() {
        if (this.selectedFile) {
            this.isUploading = true;
            this.api.uploadTrusteeImage(this.selectedFile).subscribe({
                next: (res: any) => {
                    this.newTrustee.imageUrl = res.url;
                    this.submitNewTrustee();
                },
                error: () => {
                    this.isUploading = false;
                    alert('Image upload failed. Please try again.');
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
                error: () => {
                    this.isUploading = false;
                    alert('Image upload failed. Please try again.');
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

    seedTrustees() {
        if (!confirm('This will import the 7 default trustees if they don\'t already exist. Continue?')) return;
        this.isSeeding = true;
        this.seedMessage = '';
        this.api.seedTrustees().subscribe({
            next: (res: any) => {
                this.isSeeding = false;
                this.seedMessage = `Done — inserted ${res.inserted} of ${res.total} trustees.`;
                this.loadTrustees();
            },
            error: () => {
                this.isSeeding = false;
                this.seedMessage = 'Seed failed. Check backend logs.';
            }
        });
    }
}
