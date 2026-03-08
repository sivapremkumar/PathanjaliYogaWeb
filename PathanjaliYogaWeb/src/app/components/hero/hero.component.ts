import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LucideAngularModule, Heart, Users, Calendar } from 'lucide-angular';
import { RouterModule } from '@angular/router';

@Component({
    selector: 'app-hero',
    standalone: true,
    imports: [CommonModule, LucideAngularModule, RouterModule],
    templateUrl: './hero.component.html',
    styleUrls: ['./hero.component.css']
})
export class HeroComponent implements OnInit, OnDestroy {
    readonly Heart = Heart;
    readonly Users = Users;
    readonly Calendar = Calendar;

    images: string[] = [
        'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&q=80&w=800', // original yoga
        'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&q=80&w=800', // silhouette yoga at sunset
        'https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&q=80&w=800', // yoga pose by sea
        'https://images.unsplash.com/photo-1599447421416-3414500d18a5?auto=format&fit=crop&q=80&w=800'  // hands in prayer / meditation
    ];

    currentIndex = 0;
    private timer: any;

    ngOnInit() {
        this.timer = setInterval(() => {
            this.currentIndex = (this.currentIndex + 1) % this.images.length;
        }, 3500);
    }

    ngOnDestroy() {
        if (this.timer) {
            clearInterval(this.timer);
        }
    }
}
