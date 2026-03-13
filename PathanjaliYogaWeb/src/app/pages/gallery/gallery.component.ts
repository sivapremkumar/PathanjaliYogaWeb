import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { LucideAngularModule, Image, Search } from 'lucide-angular';

@Component({
    selector: 'app-gallery',
    standalone: true,
    imports: [CommonModule, LucideAngularModule],
    templateUrl: './gallery.component.html',
    styleUrls: ['./gallery.component.css']
})
export class GalleryComponent implements OnInit {
    items: any[] = [];
    readonly Image = Image;
    readonly Search = Search;

    constructor(private api: ApiService) { }

    private readonly fallbackItems = [
        { id: 1, title: 'International Yoga Day 2025', url: 'gallery_04.jpeg', type: 'Image' },
        { id: 2, title: 'Daily morning sessions', url: 'gallery_02.jpeg', type: 'Image' },
        { id: 3, title: 'Plants distribution Program', url: 'gallery_03.jpeg', type: 'Image' },
        { id: 4, title: 'Success meet', url: 'gallery_01.jpeg', type: 'Image' },
        { id: 5, title: 'Yoga Training', url: 'gallery_05.jpeg', type: 'Image' },
        { id: 6, title: 'Daily yoga session', url: 'gallery_06.jpeg', type: 'Image' },
        { id: 7, title: 'Sponsored by', url: 'gallery_07.jpeg', type: 'Image' },
        { id: 8, title: 'Yoga for school students', url: 'gallery_08.jpeg', type: 'Image' },
        { id: 9, title: 'Yoga for Toal Gate employees', url: 'gallery_09.jpeg', type: 'Image' },
        { id: 10, title: 'Surya Namaskar', url: 'gallery_10.jpeg', type: 'Image' },
        { id: 11, title: 'Blood donation camp', url: 'gallery_11.jpeg', type: 'Image' },
        { id: 12, title: 'Yoga for all generations', url: 'gallery_12.jpeg', type: 'Image' }
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
        });
    }
}
