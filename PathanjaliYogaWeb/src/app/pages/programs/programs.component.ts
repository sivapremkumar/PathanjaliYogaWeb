import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { LucideAngularModule, Heart, BookOpen, Users, Calendar, ArrowRight } from 'lucide-angular';
import { RouterModule } from '@angular/router';

@Component({
    selector: 'app-programs',
    standalone: true,
    imports: [CommonModule, LucideAngularModule, RouterModule],
    templateUrl: './programs.component.html',
    styleUrls: ['./programs.component.css']
})
export class ProgramsListComponent implements OnInit {
    programs: any[] = [];
    currentPage = 1;
    readonly itemsPerPage = 3;
    readonly Heart = Heart;
    readonly BookOpen = BookOpen;
    readonly Users = Users;
    readonly Calendar = Calendar;
    readonly ArrowRight = ArrowRight;

    private readonly fallbackPrograms = [
        { id: 1, title: 'Traditional Padhanjali Yoga', description: 'Daily morning sessions focused on Surya Namaskar and Pranayama.', type: 'Yoga', schedule: '6:00 AM - 7:30 AM', image: 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&q=80&w=800' },
        { id: 2, title: 'Yoga for Children', description: 'Fun and interactive sessions to improve concentration and posture in kids.', type: 'Yoga', schedule: '4:30 PM - 5:30 PM', image: 'https://images.unsplash.com/photo-1552196564-972d46387347?auto=format&fit=crop&q=80&w=800' },
        { id: 3, title: 'Social Welfare Awareness', description: 'Monthly workshops about health, hygiene, and community development.', type: 'Welfare', schedule: 'Monthly Weekends', image: 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&q=80&w=800' }
    ];

    constructor(private api: ApiService) { }

    get totalPages(): number {
        return Math.max(1, Math.ceil(this.programs.length / this.itemsPerPage));
    }

    get pagedPrograms(): any[] {
        const start = (this.currentPage - 1) * this.itemsPerPage;
        return this.programs.slice(start, start + this.itemsPerPage);
    }

    goToPage(page: number) {
        if (page < 1 || page > this.totalPages || page === this.currentPage) {
            return;
        }
        this.currentPage = page;
    }

    nextPage() {
        this.goToPage(this.currentPage + 1);
    }

    previousPage() {
        this.goToPage(this.currentPage - 1);
    }

    private normalizeItem(item: any) {
        const candidate = item.imageUrl ?? item.image_url ?? item.image ?? '';
        const image = typeof candidate === 'string' && (candidate.startsWith('http') || candidate.startsWith('/api/uploads/programs/'))
            ? candidate
            : '';

        return {
            id: item.id,
            title: item.title ?? '',
            description: item.description ?? '',
            type: item.type ?? 'Program',
            schedule: item.schedule ?? '',
            image,
        };
    }

    ngOnInit() {
        this.api.getPrograms().subscribe(res => {
            const apiPrograms = res
                .map(item => this.normalizeItem(item))
                .filter(item => !!item.title);

            this.programs = apiPrograms.length > 0 ? apiPrograms : this.fallbackPrograms;
            this.currentPage = 1;
        });
    }
}

