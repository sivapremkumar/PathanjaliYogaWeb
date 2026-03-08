import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../services/api.service';
import { LucideAngularModule, Download, Search } from 'lucide-angular';

@Component({
    selector: 'app-donations',
    standalone: true,
    imports: [CommonModule, LucideAngularModule],
    templateUrl: './donations.component.html'
})
export class DonationsComponent implements OnInit {
    donations: any[] = [];
    readonly Download = Download;
    readonly Search = Search;

    constructor(private api: ApiService) { }

    ngOnInit() {
        this.api.getDonations().subscribe(data => {
            this.donations = data;
        });
    }
}
