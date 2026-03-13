import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { LucideAngularModule, Image, Search, ChevronLeft, ChevronRight } from 'lucide-angular';
import { environment } from '../../../environments/environment';

@Component({
    selector: 'app-gallery',
    standalone: true,
    imports: [CommonModule, LucideAngularModule],
    templateUrl: './gallery.component.html',
    styleUrls: ['./gallery.component.css']
})
export class GalleryComponent implements OnInit {
    items: any[] = [];
    currentPage = 1;
    readonly pageSize = 8;
    isPageTransitioning = false;
    readonly Image = Image;
    readonly Search = Search;
    readonly ChevronLeft = ChevronLeft;
    readonly ChevronRight = ChevronRight;
    readonly bannerUrl = `${environment.uploadsBase}/others/Yoga-wellness-banner.jpeg`;
    readonly galleryUploadsBase = `${environment.uploadsBase}/gallery`;

    constructor(private api: ApiService) { }

    private readonly fallbackItems = [
        { id: 1, title: 'International Yoga Day 2025', url: `${this.galleryUploadsBase}/gallery_04.jpeg`, type: 'Image' },
        { id: 2, title: 'Daily morning sessions', url: `${this.galleryUploadsBase}/gallery_02.jpeg`, type: 'Image' },
        { id: 3, title: 'Plants distribution Program', url: `${this.galleryUploadsBase}/gallery_03.jpeg`, type: 'Image' },
        { id: 4, title: 'Success meet', url: `${this.galleryUploadsBase}/gallery_01.jpeg`, type: 'Image' },
        { id: 5, title: 'Yoga Training', url: `${this.galleryUploadsBase}/gallery_05.jpeg`, type: 'Image' },
        { id: 6, title: 'Daily yoga session', url: `${this.galleryUploadsBase}/gallery_06.jpeg`, type: 'Image' },
        { id: 7, title: 'Sponsored by', url: `${this.galleryUploadsBase}/gallery_07.jpeg`, type: 'Image' },
        { id: 8, title: 'Yoga for school students', url: `${this.galleryUploadsBase}/gallery_08.jpeg`, type: 'Image' },
        { id: 9, title: 'Yoga for Toal Gate employees', url: `${this.galleryUploadsBase}/gallery_09.jpeg`, type: 'Image' },
        { id: 10, title: 'Surya Namaskar', url: `${this.galleryUploadsBase}/gallery_10.jpeg`, type: 'Image' },
        { id: 11, title: 'Blood donation camp', url: `${this.galleryUploadsBase}/gallery_11.jpeg`, type: 'Image' },
        { id: 12, title: 'Yoga for all generations', url: `${this.galleryUploadsBase}/gallery_12.jpeg`, type: 'Image' }
    ];

    ngOnInit() {
        this.api.getGallery().subscribe(res => {
            const apiItems = res
                .map(item => ({
                    id: item.id,
                    title: item.title ?? 'Gallery Item',
                    url: (() => {
                        const candidate = item.imageUrl ?? item.image_url ?? item.location ?? '';
                        return (typeof candidate === 'string' && (candidate.startsWith('http') || candidate.startsWith('/api/uploads/gallery/')))
                            ? candidate
                            : '';
                    })(),
                    type: 'Image'
                }))
                .filter(item => !!item.url);

            this.items = apiItems.length > 0 ? apiItems : this.fallbackItems;
            this.currentPage = 1;
        });
    }

    get totalPages(): number {
        return Math.max(1, Math.ceil(this.items.length / this.pageSize));
    }

    get paginatedItems(): any[] {
        const start = (this.currentPage - 1) * this.pageSize;
        return this.items.slice(start, start + this.pageSize);
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
