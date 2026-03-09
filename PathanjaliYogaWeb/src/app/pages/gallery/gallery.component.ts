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

    ngOnInit() {
        this.api.getNews().subscribe(res => {
            // Mock gallery items
        });

        // this.items = [
        //     { id: 1, title: 'Yoga in the Morning', url: 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&q=80&w=800', type: 'Image' },
        //     { id: 2, title: 'Community Welfare Event', url: 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&q=80&w=800', type: 'Image' },
        //     { id: 3, title: 'Yoga for All', url: 'https://images.unsplash.com/photo-1552196564-972d46387347?auto=format&fit=crop&q=80&w=800', type: 'Image' },
        //     { id: 4, title: 'Meditation Session', url: 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&q=80&w=800', type: 'Image' },
        //     { id: 5, title: 'Global Yoga Day', url: 'https://images.unsplash.com/photo-1575052814086-f385e2e2ad1b?auto=format&fit=crop&q=80&w=800', type: 'Image' },
        //     { id: 6, title: 'Peace & Harmony', url: 'https://images.unsplash.com/photo-1510894347713-fc3ed6fdf539?auto=format&fit=crop&q=80&w=800', type: 'Image' }
        // ];

        this.items = [
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
    }
}
